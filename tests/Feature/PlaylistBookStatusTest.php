<?php

namespace Tests\Feature;

use App\Models\Books;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBuyBook;
use App\Models\UserSavedBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlaylistBookStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $roleName = 'user'): User
    {
        $role = Role::firstOrCreate(['role' => $roleName]);

        return User::create([
            'name' => ucfirst($roleName) . ' User',
            'email' => $roleName . '-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ])->fresh();
    }

    private function createBook(string $title, float $price = 0.0): Books
    {
        return Books::create([
            'title' => $title,
            'description' => 'Book for playlist status tests',
            'price' => $price,
        ])->fresh();
    }

    /**
     * Locate a book entry within the playlist response payload by book id.
     *
     * @param  array<int, array<string, mixed>>  $books
     * @return array<string, mixed>
     */
    private function bookRow(array $books, string $bookId): array
    {
        foreach ($books as $row) {
            if (($row['id'] ?? null) === $bookId) {
                return $row;
            }
        }

        $this->fail("Book {$bookId} not found in playlist response");
    }

    public function test_playlist_books_report_saved_purchased_and_payment_session_status(): void
    {
        $owner = $this->createUser('user');

        $boughtBook = $this->createBook('Bought Book', 9.99);
        $savedBook = $this->createBook('Saved Book');
        $pendingBook = $this->createBook('Pending Book', 12.50);
        $plainBook = $this->createBook('Plain Book');

        // Already bought (paid) — matched on user_id + book_id.
        UserBuyBook::create([
            'user_id' => $owner->id,
            'book_id' => $boughtBook->id,
            'amount' => 9.99,
            'payment_method' => 'card',
            'status' => 'paid',
            'stripe_checkout_session_id' => 'cs_test_paid_123',
            'purchased_at' => now(),
        ]);

        // Already added (saved) to the user's library.
        UserSavedBook::create([
            'user_id' => $owner->id,
            'book_id' => $savedBook->id,
        ]);

        // In-flight Stripe payment session (pending checkout).
        UserBuyBook::create([
            'user_id' => $owner->id,
            'book_id' => $pendingBook->id,
            'amount' => 12.50,
            'payment_method' => 'card',
            'status' => 'pending',
            'stripe_checkout_session_id' => 'cs_test_pending_456',
        ]);

        Sanctum::actingAs($owner, ['*']);

        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'Status List',
            'is_public' => true,
            'book_ids' => [$boughtBook->id, $savedBook->id, $pendingBook->id, $plainBook->id],
        ])->assertCreated()->json('data.id');

        $books = $this->getJson("/api/v1/playlists/{$playlistId}")
            ->assertOk()
            ->json('data.books');

        // Bought book — purchased flag set, payment session shows paid.
        $bought = $this->bookRow($books, $boughtBook->id);
        $this->assertTrue($bought['user_has_purchased']);
        $this->assertFalse($bought['user_has_saved']);
        $this->assertSame('paid', $bought['purchase']['status']);
        $this->assertFalse($bought['purchase']['payment_pending']);
        $this->assertSame('cs_test_paid_123', $bought['purchase']['checkout_session_id']);

        // Saved book — added flag set, no purchase record.
        $saved = $this->bookRow($books, $savedBook->id);
        $this->assertTrue($saved['user_has_saved']);
        $this->assertFalse($saved['user_has_purchased']);
        $this->assertNull($saved['purchase']);

        // Pending book — Stripe session in progress; status_url points at the
        // Stripe check endpoint so the frontend can verify it.
        $pending = $this->bookRow($books, $pendingBook->id);
        $this->assertFalse($pending['user_has_purchased']);
        $this->assertSame('pending', $pending['purchase']['status']);
        $this->assertTrue($pending['purchase']['payment_pending']);
        $this->assertSame('cs_test_pending_456', $pending['purchase']['checkout_session_id']);
        $this->assertStringContainsString(
            '/api/v1/stripe/status?session_id=cs_test_pending_456',
            $pending['purchase']['status_url']
        );

        // Plain book — nothing recorded for this user.
        $plain = $this->bookRow($books, $plainBook->id);
        $this->assertFalse($plain['user_has_saved']);
        $this->assertFalse($plain['user_has_purchased']);
        $this->assertNull($plain['purchase']);
    }

    public function test_payway_pending_purchase_exposes_status_url(): void
    {
        $owner = $this->createUser('user');
        $book = $this->createBook('PayWay Book', 8.00);

        $tranId = str_replace('-', '', (string) Str::uuid());

        UserBuyBook::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'amount' => 8.00,
            'payment_method' => 'payway_khqr',
            'status' => 'pending',
            'payway_tran_id' => $tranId,
        ]);

        Sanctum::actingAs($owner, ['*']);

        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'PayWay List',
            'is_public' => true,
            'book_ids' => [$book->id],
        ])->assertCreated()->json('data.id');

        $row = $this->bookRow(
            $this->getJson("/api/v1/playlists/{$playlistId}")->assertOk()->json('data.books'),
            $book->id
        );

        $this->assertSame('pending', $row['purchase']['status']);
        $this->assertSame($tranId, $row['purchase']['payway_tran_id']);
        $this->assertStringContainsString('/api/v1/payway/status?tran_id=' . $tranId, $row['purchase']['status_url']);
    }

    public function test_guest_playlist_books_have_neutral_status(): void
    {
        $owner = $this->createUser('user');
        $book = $this->createBook('Guest Visible Book', 5.00);

        // A purchase by the owner must not leak into a guest's view.
        UserBuyBook::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'payment_method' => 'card',
            'status' => 'paid',
            'purchased_at' => now(),
        ]);

        Sanctum::actingAs($owner, ['*']);
        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'Public Status List',
            'is_public' => true,
            'book_ids' => [$book->id],
        ])->assertCreated()->json('data.id');

        // Guest request (no auth).
        $this->app['auth']->forgetGuards();

        $row = $this->bookRow(
            $this->getJson("/api/v1/playlists/{$playlistId}")->assertOk()->json('data.books'),
            $book->id
        );

        $this->assertFalse($row['user_has_saved']);
        $this->assertFalse($row['user_has_purchased']);
        $this->assertNull($row['purchase']);
    }
}
