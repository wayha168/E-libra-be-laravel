<?php

namespace Tests\Feature\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Books;
use App\Models\Playlist;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class UserSavedBooksTest extends TestCase
{
    use RefreshDatabase;

    /** Setup test data. */
    protected function setUp(): void
    {
        parent::setUp();
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['role' => $name]);
    }

    private function createUser(string $roleName = 'user', array $overrides = []): User
    {
        $role = $this->role($roleName);

        return User::create(array_merge([
            'name' => ucfirst($roleName) . ' User',
            'email' => $roleName . '-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ], $overrides));
    }

    private function createBook(array $overrides = []): Books
    {
        return Books::create(array_merge([
            'title' => 'Test Book - ' . uniqid(),
            'description' => 'Test book description',
            'price' => 0,
            'status' => 'published',
        ], $overrides))->fresh();
    }

    private function createPlaylist(User $user, array $overrides = []): Playlist
    {
        return Playlist::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Test Playlist - ' . uniqid(),
            'is_public' => true,
        ], $overrides));
    }

    /** Test saving a book. */
    public function test_save_book(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Book saved successfully')
            ->assertJsonPath('data.saved', true);

        // Verify in database
        $this->assertDatabaseHas('user_saved_books', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /** Test saving a book with notes. */
    public function test_save_book_with_notes(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);
        $notes = 'This is a great book for learning';

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save", ['notes' => $notes]);

        $response->assertStatus(201)
            ->assertJsonPath('data.saved', true);

        $this->assertDatabaseHas('user_saved_books', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'notes' => $notes,
        ]);
    }

    /** Test saving the same book twice (should not create duplicate). */
    public function test_save_book_already_saved(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Save once
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        // Try to save again
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Book already saved');

        // Should only have one record
        $this->assertEquals(1, $user->savedBooks()->count());
    }

    /** Test unsaving a book. */
    public function test_unsave_book(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Save first
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        // Then unsave
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/unsave");

        $response->assertStatus(200)
            ->assertJsonPath('data.saved', false);

        // Verify removed from database
        $this->assertDatabaseMissing('user_saved_books', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /** Test toggling save status. */
    public function test_toggle_save_status(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Toggle ON
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save/toggle");

        $response->assertStatus(200)
            ->assertJsonPath('data.saved', true);

        // Toggle OFF
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save/toggle");

        $response->assertStatus(200)
            ->assertJsonPath('data.saved', false);
    }

    /** Test checking if a book is saved. */
    public function test_check_book_saved_status(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Not saved yet
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/books/{$book->id}/saved");
        $response->assertJsonPath('data.saved', false);

        // Save it
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        // Now should be saved
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/books/{$book->id}/saved");
        $response->assertJsonPath('data.saved', true);
    }

    /** Test retrieving user's saved books. */
    public function test_get_user_saved_books(): void
    {
        $user = $this->createUser();
        $book1 = $this->createBook(['status' => 'published', 'title' => 'First Book']);
        $book2 = $this->createBook(['status' => 'published', 'title' => 'Second Book']);

        // Save two books
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/books/{$book1->id}/save");
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/books/{$book2->id}/save");

        // Get list
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/saved-books');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Saved books fetched successfully');

        // Verify count
        $this->assertEquals(2, $user->savedBooks()->count());
    }

    /** Test adding saved book to existing playlist. */
    public function test_add_saved_book_to_existing_playlist(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);
        $playlist = $this->createPlaylist($user);

        // Save book
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        // Add to playlist
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/add-to-playlist", [
                'playlist_id' => $playlist->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Book added to playlist');

        // Verify book is in playlist
        $this->assertTrue(
            $playlist->books()->where('books.id', $book->id)->exists()
        );
    }

    /** Test creating new playlist from saved book operation. */
    public function test_add_saved_book_to_new_playlist(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Save book and create new playlist
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/add-to-playlist", [
                'playlist_name' => 'My Reading List',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Book added to playlist');

        // Verify playlist was created
        $this->assertDatabaseHas('playlists', [
            'user_id' => $user->id,
            'name' => 'My Reading List',
            'is_public' => false,
        ]);

        // Verify book is in the new playlist
        $playlist = $user->playlists()
            ->where('name', 'My Reading List')
            ->first();
        $this->assertTrue($playlist->books()->where('books.id', $book->id)->exists());
    }

    /** Test user cannot add book to another user's playlist. */
    public function test_cannot_add_to_other_users_playlist(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $book = $this->createBook(['status' => 'published']);
        $playlist = $this->createPlaylist($user1);

        // User2 tries to add book to User1's playlist
        $response = $this->actingAs($user2, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/add-to-playlist", [
                'playlist_id' => $playlist->id,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'You do not have permission to add books to this playlist');
    }

    /** Test offline cache for saved books. */
    public function test_get_offline_cache(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Save book
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        // Get offline cache
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/offline-cache');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Offline cache data prepared')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.total_books', 1);
    }

    /** Test offline cache for single book. */
    public function test_get_offline_book_cache(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Save book
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        // Get offline data for that book
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/offline-cache/book/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', $book->title);
    }

    /** Test user relationships with saved books. */
    public function test_user_has_saved_books_relationship(): void
    {
        $user = $this->createUser();
        $book1 = $this->createBook(['status' => 'published']);
        $book2 = $this->createBook(['status' => 'published']);

        // Save books via eloquent
        $user->savedBooks()->create([
            'book_id' => $book1->id,
        ]);
        $user->savedBooks()->create([
            'book_id' => $book2->id,
        ]);

        // Verify relationship
        $this->assertEquals(2, $user->savedBooks()->count());
        $this->assertTrue($user->savedBooks()->where('book_id', $book1->id)->exists());
        $this->assertTrue($user->savedBooks()->where('book_id', $book2->id)->exists());
    }

    /** Test book has many saved records (from different users). */
    public function test_book_has_saved_by_users_relationship(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Both users save the same book
        $user1->savedBooks()->create(['book_id' => $book->id]);
        $user2->savedBooks()->create(['book_id' => $book->id]);

        // Verify relationship
        $this->assertEquals(2, $book->savedByUsers()->count());
    }

    /** Test user tracking with saved books (user_id persists). */
    public function test_saved_books_track_user_id(): void
    {
        $user = $this->createUser();
        $book = $this->createBook(['status' => 'published']);

        // Save book
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/save");

        // Verify user_id is correctly stored
        $savedBook = $user->savedBooks()->first();
        $this->assertEquals($user->id, $savedBook->user_id);
        $this->assertEquals($book->id, $savedBook->book_id);
    }
}
