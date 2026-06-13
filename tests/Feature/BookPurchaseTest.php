<?php

namespace Tests\Feature;

use App\Models\Books;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCategoryPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_purchase_book_and_access_gated_pdf(): void
    {
        // 1. Create a user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => \Hash::make('password'),
            'confirm_password' => 'password',
        ]);

        // 2. Create a premium book
        $book = Books::create([
            'title' => 'Test Premium Book',
            'description' => 'A paid book description',
            'price' => 9.99,
            'pdf_file' => 'https://example.com/test.pdf',
        ]);

        // 3. Unauthenticated request to GET /api/v1/books/{book} should mask the PDF URL
        $response = $this->getJson("/api/v1/books/{$book->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.pdf_file', null);

        // 4. Authenticated (but not purchased/subscribed) request to GET /api/v1/books/{book} should still mask PDF URL
        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/books/{$book->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.pdf_file', null);

        // 5. Authenticated request to download gated PDF should return 403 Forbidden
        $response = $this->getJson("/api/v1/books/{$book->id}/download");
        $response->assertStatus(403);

        // 6. Authenticated request to POST /api/v1/books/{book}/buy should succeed
        $response = $this->postJson("/api/v1/books/{$book->id}/buy");
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['id', 'user_id', 'book_id', 'amount', 'status']]);

        $this->assertDatabaseHas('users_buys_book', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 9.99,
            'status' => 'paid',
        ]);

        // 7. Request to GET /api/v1/books/{book} now should unmask PDF URL
        $response = $this->getJson("/api/v1/books/{$book->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.pdf_file', 'https://example.com/test.pdf');

        // 8. Request to download gated PDF should now redirect/allow download
        $response = $this->getJson("/api/v1/books/{$book->id}/download");
        $response->assertRedirect('https://example.com/test.pdf');
    }

    public function test_subscriber_can_access_gated_pdf_without_buying(): void
    {
        // 1. Create a user (not yet subscribed)
        $user = User::create([
            'name' => 'Jane Subscriber',
            'email' => 'jane@example.com',
            'password' => \Hash::make('password'),
            'confirm_password' => 'password',
            'user_subscribe' => false,
        ]);

        // 2. Create a premium book
        $book = Books::create([
            'title' => 'Test Premium Book',
            'description' => 'A paid book description',
            'price' => 5.00,
            'pdf_file' => 'https://example.com/premium.pdf',
        ]);

        Sanctum::actingAs($user);

        // 3. Fetching user info (/api/v1/me) should show user_subscribe is false
        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(200)
            ->assertJsonPath('data.user_subscribe', false);

        // 4. User subscribes using POST /api/v1/user/subscribe
        $response = $this->postJson('/api/v1/user/subscribe');
        $response->assertStatus(200)
            ->assertJsonPath('data.user_subscribe', true);

        $this->assertTrue($user->fresh()->user_subscribe);

        // 5. Fetching book details now should unmask the PDF url
        $response = $this->getJson("/api/v1/books/{$book->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.pdf_file', 'https://example.com/premium.pdf');

        // 6. Download book should redirect to PDF url
        $response = $this->getJson("/api/v1/books/{$book->id}/download");
        $response->assertRedirect('https://example.com/premium.pdf');
    }

    public function test_per_user_category_permissions_gating(): void
    {
        // 1. Create role and permissions
        $userRole = Role::create([
            'role' => 'user',
            'display_name' => 'User',
        ]);

        $viewPermission = Permission::create([
            'name' => 'view_categories',
            'display_name' => 'View Categories',
        ]);

        // 2. Create a user with 'user' role
        $user = User::create([
            'name' => 'Category Manager',
            'email' => 'manager@example.com',
            'password' => \Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $userRole->id,
        ]);

        // 3. Create a category
        $category = Category::create([
            'name' => 'Top Secret Books',
            'slug' => 'top-secret',
        ]);

        // 4. Authenticate as this user
        $this->actingAs($user);

        // 5. Initially, user has NO permission, so show view should fail (throws 403)
        $response = $this->get("/dashboard/categories/{$category->id}");
        $response->assertStatus(403);

        // 6. Grant per-user category permission in user_category_permissions
        UserCategoryPermission::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'permission_id' => $viewPermission->id,
        ]);

        // 7. Requesting GET /dashboard/categories/{category} should now succeed (returns 200)
        $response = $this->get("/dashboard/categories/{$category->id}");
        $response->assertStatus(200);
    }
}
