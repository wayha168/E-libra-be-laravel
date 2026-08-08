<?php

namespace Tests\Feature;

use App\Models\AbaPaywayMerchant;
use App\Models\Author;
use App\Models\Books;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBuyBook;
use App\Support\BookPdfPreviewGenerator;
use App\Support\PurchaseCommission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ElibraFeatureApisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.stripe.secret' => null,
            'elibra.book_pdf_max_kb' => 524288,
            'elibra.admin_commission_rate' => 10,
            'elibra.book_trial_pages' => 15,
        ]);
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(
            ['role' => $name],
            ['display_name' => ucfirst(str_replace('_', ' ', $name))]
        );
    }

    private function createUser(string $roleName = 'user', array $overrides = []): User
    {
        $role = $this->role($roleName);

        return User::create(array_merge([
            'name' => ucfirst($roleName) . ' User',
            'email' => $roleName . '-' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ], $overrides));
    }

    private function seedPaidBookWithAuthor(?User $authorUser = null, ?string $previewRelative = 'books/test-preview.pdf'): Books
    {
        Storage::disk('local')->makeDirectory('books');

        $source = base_path('tests/fixtures/sample-3pages.pdf');
        $fullRelative = 'books/test-full-' . uniqid() . '.pdf';
        $fullPath = Storage::disk('local')->path($fullRelative);
        copy($source, $fullPath);

        $previewRelativePath = null;
        if ($previewRelative !== null) {
            $previewRelativePath = str_replace('test-preview.pdf', 'test-preview-' . uniqid() . '.pdf', $previewRelative);
            $previewAbs = Storage::disk('local')->path($previewRelativePath);
            BookPdfPreviewGenerator::generate($fullPath, $previewAbs, 2);
            if (! is_readable($previewAbs)) {
                copy($fullPath, $previewAbs);
            }
        }

        $authorUser ??= $this->createUser('author');
        $author = Author::create([
            'user_id' => $authorUser->id,
            'bio' => 'Author bio',
            'facebook' => 'https://facebook.com/author',
        ]);

        return Books::create([
            'title' => 'Paid Book',
            'description' => 'Desc',
            'price' => 10.00,
            'author_id' => $author->id,
            'pdf_file' => $fullRelative,
            'pdf_preview_path' => $previewRelativePath,
        ])->fresh();
    }

    public function test_api_book_upload_persists_pdf_for_admin(): void
    {
        Storage::fake('local');
        $admin = $this->createUser('admin');
        Sanctum::actingAs($admin, ['*']);

        $pdf = UploadedFile::fake()->create('book.pdf', 100, 'application/pdf');

        $response = $this->post('/api/v1/books', [
            'title' => 'Uploaded Book',
            'description' => 'Via API',
            'price' => 5,
            'pdf_file' => $pdf,
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('message', 'Book created successfully');

        $book = Books::where('title', 'Uploaded Book')->first();
        $this->assertNotNull($book);
        $this->assertNotNull($book->pdf_file);
        $this->assertTrue(Storage::disk('local')->exists($book->pdf_file));
    }

    public function test_preview_returns_404_when_preview_path_missing(): void
    {
        $book = $this->seedPaidBookWithAuthor(previewRelative: null);
        $book->update(['pdf_preview_path' => null]);

        $this->getJson("/api/v1/books/{$book->id}/preview")
            ->assertStatus(404)
            ->assertJsonPath('message', 'Preview file not found.');

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.can_preview', false);
    }

    public function test_admin_can_manage_author_social_via_api(): void
    {
        $this->role('author');
        $admin = $this->createUser('admin');
        Sanctum::actingAs($admin, ['*']);

        $create = $this->postJson('/api/v1/authors', [
            'mode' => 'new_account',
            'name' => 'Social Author',
            'email' => 'social-author@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'bio' => 'Hello',
            'instagram' => 'https://instagram.com/elibra',
            'website' => 'https://elibra.example',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.instagram', 'https://instagram.com/elibra')
            ->assertJsonPath('data.website', 'https://elibra.example');

        $authorId = $create->json('data.id');

        $this->putJson("/api/v1/authors/{$authorId}", [
            'bio' => 'Updated bio',
            'tiktok' => 'https://tiktok.com/@elibra',
        ])->assertOk()
            ->assertJsonPath('data.tiktok', 'https://tiktok.com/@elibra')
            ->assertJsonPath('data.bio', 'Updated bio');

        $this->getJson("/api/v1/authors/{$authorId}")
            ->assertOk()
            ->assertJsonPath('data.instagram', 'https://instagram.com/elibra');
    }

    public function test_payway_khqr_buy_requires_merchant_and_callback_applies_commission(): void
    {
        $buyer = $this->createUser('user');
        $authorUser = $this->createUser('author');
        $book = $this->seedPaidBookWithAuthor($authorUser);

        Sanctum::actingAs($buyer, ['*']);

        $this->postJson("/api/v1/books/{$book->id}/buy", [
            'payment_method' => 'payway_khqr',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'payway_not_configured');

        AbaPaywayMerchant::create([
            'user_id' => $authorUser->id,
            'merchant_id' => 'MERCHANT1',
            'api_key' => 'test-api-key-secret',
            'merchant_name' => 'Author Shop',
            'environment' => 'sandbox',
            'currency' => 'USD',
            'payment_option' => 'abapay_khqr',
            'is_active' => true,
        ]);

        $this->getJson("/api/v1/books/{$book->id}/payment-options")
            ->assertOk()
            ->assertJsonPath('data.methods.payway_khqr.available', true)
            ->assertJsonPath('data.trial_pages', 15);

        $buy = $this->postJson("/api/v1/books/{$book->id}/buy", [
            'payment_method' => 'payway_khqr',
        ]);

        $buy->assertCreated()
            ->assertJsonPath('data.provider', 'aba_payway')
            ->assertJsonPath('data.payment_method', 'payway_khqr');

        $this->assertNotEmpty($buy->json('data.fields.hash'));
        $this->assertNotEmpty($buy->json('data.endpoint'));

        $purchaseId = $buy->json('data.purchase.id');
        $tranId = $buy->json('data.tran_id');

        $this->assertDatabaseHas('users_buys_book', [
            'id' => $purchaseId,
            'status' => 'pending',
            'payway_tran_id' => $tranId,
        ]);

        $callback = $this->postJson('/api/v1/payway/callback', [
            'tran_id' => $tranId,
            'status' => '0',
            'return_params' => 'purchase_id=' . $purchaseId,
        ]);

        $callback->assertOk()
            ->assertJsonPath('data.purchase.status', 'paid');

        $purchase = UserBuyBook::find($purchaseId);
        $this->assertSame('paid', $purchase->status);
        $this->assertEquals(1.0, (float) $purchase->admin_commission_amount);
        $this->assertEquals(9.0, $purchase->authorEarnings());

        Sanctum::actingAs($authorUser, ['*']);
        $earnings = $this->getJson('/api/v1/author/earnings')->assertOk();
        $this->assertEquals(9.0, (float) $earnings->json('data.net_earnings'));
        $this->assertEquals(1.0, (float) $earnings->json('data.platform_fee_total'));
        $this->assertEquals(PurchaseCommission::rate(), (float) $earnings->json('data.platform_fee_rate'));
    }

    public function test_admin_purchases_summary_includes_company_cut(): void
    {
        $admin = $this->createUser('admin');
        $buyer = $this->createUser('user');
        $book = $this->seedPaidBookWithAuthor();

        $purchase = UserBuyBook::create([
            'user_id' => $buyer->id,
            'book_id' => $book->id,
            'amount' => 20,
            'payment_method' => 'card',
            'status' => 'paid',
            'purchased_at' => now(),
        ]);
        PurchaseCommission::applyToPurchase($purchase);

        Sanctum::actingAs($admin, ['*']);
        $this->getJson('/api/v1/purchases')
            ->assertOk()
            ->assertJsonPath('summary.company_commission_total', 2)
            ->assertJsonPath('summary.author_net_total', 18);
    }
}
