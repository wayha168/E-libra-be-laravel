<?php

namespace Tests\Feature;

use App\Models\Books;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBuyBook;
use App\Support\BookPdfStorage;
use App\Support\BookReadFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class BookReadFileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Author a real, FPDI-importable PDF on disk and return its absolute path.
     * The bundled fixture is intentionally malformed, so we build our own.
     */
    private function makeSourcePdf(int $pages = 3, int $linesPerPage = 40): string
    {
        $pdf = new Fpdi();

        for ($p = 1; $p <= $pages; $p++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            for ($l = 0; $l < $linesPerPage; $l++) {
                $pdf->Cell(0, 6, "Page {$p} line {$l} — e-Libra read file test content.", 0, 1);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'src-') . '.pdf';
        $pdf->Output('F', $path);

        return $path;
    }

    private function createUser(array $overrides = []): User
    {
        $role = Role::firstOrCreate(['role' => 'user']);

        return User::create(array_merge([
            'name' => 'Reader',
            'email' => 'reader-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
            'trial_ends_at' => now()->subDay(), // disable the signup trial
        ], $overrides))->fresh();
    }

    public function test_generate_creates_compact_base64_read_file(): void
    {
        $source = $this->makeSourcePdf();

        $result = BookReadFile::generate($source);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['size']);
        $this->assertNotEmpty($result['data']);

        $decoded = base64_decode($result['data'], true);
        $this->assertNotFalse($decoded);
        $this->assertSame('%PDF', substr($decoded, 0, 4));
        $this->assertSame($result['size'], strlen($decoded));

        @unlink($source);
    }

    public function test_generate_returns_null_when_over_size_cap(): void
    {
        $source = $this->makeSourcePdf();

        // Confirm it generates under a generous cap, but not under a 1 KB cap.
        $this->assertIsArray(BookReadFile::generate($source, 4096));
        $this->assertNull(BookReadFile::generate($source, 1));

        @unlink($source);
    }

    public function test_generate_returns_null_for_unreadable_source(): void
    {
        $this->assertNull(BookReadFile::generate('/no/such/file.pdf'));
    }

    public function test_store_upload_persists_read_file_columns(): void
    {
        Storage::fake('local');

        $source = $this->makeSourcePdf();
        $upload = new UploadedFile($source, 'book.pdf', 'application/pdf', null, true);

        $stored = BookPdfStorage::storeUpload($upload);

        $this->assertArrayHasKey('pdf_read_data', $stored);
        $this->assertNotEmpty($stored['pdf_read_data']);
        $this->assertGreaterThan(0, $stored['pdf_read_size']);

        // The stored payload round-trips through the model and can be decoded.
        $book = Books::create(array_merge(
            ['title' => 'Uploaded Book', 'description' => 'x', 'price' => 0],
            $stored
        ))->fresh();

        $this->assertTrue(BookReadFile::has($book));
        $this->assertSame('%PDF', substr(BookReadFile::decode($book), 0, 4));
    }

    public function test_offline_book_returns_read_file_for_accessible_book(): void
    {
        $user = $this->createUser();

        $book = Books::create([
            'title' => 'Free Readable Book',
            'description' => 'x',
            'price' => 0, // free -> accessible to everyone
            'pdf_file' => 'books/free.pdf',
            'pdf_read_data' => base64_encode('%PDF-1.4 offline bytes'),
            'pdf_read_size' => strlen('%PDF-1.4 offline bytes'),
        ])->fresh();

        Sanctum::actingAs($user, ['*']);
        $this->postJson("/api/v1/books/{$book->id}/save")->assertCreated();

        $this->getJson("/api/v1/offline-cache/book/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.has_read_file', true)
            ->assertJsonPath('data.read_file_available', true)
            ->assertJsonPath('data.read_file', base64_encode('%PDF-1.4 offline bytes'));
    }

    public function test_offline_book_gates_read_file_for_unpurchased_paid_book(): void
    {
        $user = $this->createUser();

        $book = Books::create([
            'title' => 'Paid Gated Book',
            'description' => 'x',
            'price' => 9.99, // paid -> requires entitlement
            'pdf_file' => 'books/paid.pdf',
            'pdf_read_data' => base64_encode('%PDF-1.4 secret'),
            'pdf_read_size' => strlen('%PDF-1.4 secret'),
        ])->fresh();

        Sanctum::actingAs($user, ['*']);
        $this->postJson("/api/v1/books/{$book->id}/save")->assertCreated();

        // No purchase/subscription/trial -> bytes withheld but presence is known.
        $this->getJson("/api/v1/offline-cache/book/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.has_read_file', true)
            ->assertJsonPath('data.read_file_available', false)
            ->assertJsonPath('data.read_file', null);

        // After purchasing, the bytes are released.
        UserBuyBook::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 9.99,
            'payment_method' => 'card',
            'status' => 'paid',
            'purchased_at' => now(),
        ]);

        $this->getJson("/api/v1/offline-cache/book/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.read_file_available', true)
            ->assertJsonPath('data.read_file', base64_encode('%PDF-1.4 secret'));
    }

    public function test_offline_cache_list_flags_read_file_without_bytes(): void
    {
        $user = $this->createUser();

        $book = Books::create([
            'title' => 'Listed Book',
            'description' => 'x',
            'price' => 0,
            'pdf_file' => 'books/listed.pdf',
            'pdf_read_data' => base64_encode('%PDF-1.4 bytes'),
            'pdf_read_size' => 1234,
        ])->fresh();

        Sanctum::actingAs($user, ['*']);
        $this->postJson("/api/v1/books/{$book->id}/save")->assertCreated();

        $data = $this->getJson('/api/v1/offline-cache')->assertOk()->json('data.books.0');

        $this->assertTrue($data['has_read_file']);
        $this->assertSame(1234, $data['read_file_size']);
        $this->assertStringContainsString("/api/v1/offline-cache/book/{$book->id}", $data['read_file_url']);
        // The heavy base64 payload must NOT be inlined in the list response.
        $this->assertArrayNotHasKey('read_file', $data);
    }
}
