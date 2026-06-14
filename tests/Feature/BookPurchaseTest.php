<?php

namespace Tests\Feature;

use App\Models\Books;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCategoryPermission;
use App\Support\BookPdfPreviewGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => null]);
    }

    private function seedBookWithPdf(float $price = 9.99): Books
    {
        Storage::disk('local')->makeDirectory('books');

        $source = base_path('tests/fixtures/sample-3pages.pdf');
        $fullPath = Storage::disk('local')->path('books/test-full.pdf');
        $previewPath = Storage::disk('local')->path('books/test-preview.pdf');

        copy($source, $fullPath);
        BookPdfPreviewGenerator::generate($fullPath, $previewPath, 2);

        if (!is_readable($previewPath)) {
            copy($fullPath, $previewPath);
        }

        return Books::create([
            'title' => 'Test Premium Book',
            'description' => 'A paid book description',
            'price' => $price,
            'pdf_file' => 'books/test-full.pdf',
            'pdf_preview_path' => 'books/test-preview.pdf',
        ])->fresh();
    }

    private function createUser(array $overrides = []): User
    {
        $role = Role::firstOrCreate(
            ['role' => 'user'],
            ['display_name' => 'User']
        );

        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => \Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
        ], $overrides));
    }

    public function test_user_can_purchase_book_and_access_gated_pdf(): void
    {
        $user = $this->createUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $book = $this->seedBookWithPdf();

        $this->assertTrue(Storage::disk('local')->exists('books/test-full.pdf'));
        $this->assertSame(9.99, (float) $book->price);
        $this->assertFalse(\App\Support\BookAccess::canAccessFull(null, $book));
        $this->assertTrue(\App\Support\BookAccess::hasPdf($book));

        $response = $this->getJson("/api/v1/books/{$book->id}");
        $response->assertStatus(200);
        $this->assertSame(9.99, (float) $response->json('data.price'));
        $response->assertJsonPath('data.has_full_access', false)
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.has_pdf', true);
        $this->assertArrayNotHasKey('pdf_file', $response->json('data') ?? []);

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson("/api/v1/books/{$book->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.has_full_access', false)
            ->assertJsonPath('data.can_preview', true);

        $response = $this->getJson("/api/v1/books/{$book->id}/download");
        $response->assertStatus(403);

        $response = $this->get("/api/v1/books/{$book->id}/preview");
        $response->assertOk()->assertHeader('content-type', 'application/pdf');

        $response = $this->postJson("/api/v1/books/{$book->id}/buy");
        $response->assertStatus(201);

        $this->assertDatabaseHas('users_buys_book', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 9.99,
            'status' => 'paid',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.has_full_access', true)
            ->assertJsonPath('data.can_preview', false);

        Sanctum::actingAs($user->fresh(), ['*']);
        $response = $this->getJson("/api/v1/books/{$book->id}/download");
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_subscriber_can_access_gated_pdf_without_buying(): void
    {
        $user = $this->createUser([
            'name' => 'Jane Subscriber',
            'email' => 'jane@example.com',
            'user_subscribe' => false,
        ]);

        $book = $this->seedBookWithPdf(5.00);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(200)
            ->assertJsonPath('data.user_subscribe', false);

        $response = $this->postJson('/api/v1/user/subscribe');
        $response->assertSuccessful()
            ->assertJsonPath('data.user_subscribe', true);

        $this->assertTrue($user->fresh()->user_subscribe);

        $response = $this->getJson("/api/v1/books/{$book->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.has_full_access', true);

        Sanctum::actingAs($user->fresh(), ['*']);
        $response = $this->getJson("/api/v1/books/{$book->id}/download");
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_per_user_category_permissions_gating(): void
    {
        $authorRole = Role::firstOrCreate(['role' => 'author'], ['display_name' => 'Author']);

        $viewPermission = Permission::create([
            'name' => 'view_categories',
            'display_name' => 'View Categories',
        ]);

        $user = User::create([
            'name' => 'Category Manager',
            'email' => 'manager@example.com',
            'password' => \Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $authorRole->id,
        ]);

        $category = Category::create([
            'name' => 'Top Secret Books',
            'slug' => 'top-secret',
        ]);

        UserCategoryPermission::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'permission_id' => $viewPermission->id,
        ]);

        $this->assertDatabaseHas('user_category_permissions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'permission_id' => $viewPermission->id,
        ]);
    }
}
