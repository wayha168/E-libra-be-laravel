<?php

namespace Tests\Feature;

use App\Models\Books;
use App\Models\BookLike;
use App\Models\Playlist;
use App\Models\PlaylistLike;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserLikesAndPlaylistsTest extends TestCase
{
    use RefreshDatabase;

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

    private function createBook(string $title = 'Test Book'): Books
    {
        return Books::create([
            'title' => $title,
            'description' => 'Test book description',
            'price' => 9.99,
        ])->fresh();
    }

    /**
     * Test that book likes properly track user_id
     */
    public function test_book_like_tracks_correct_user_id(): void
    {
        $user1 = $this->createUser('user');
        $user2 = $this->createUser('user');
        $book = $this->createBook('Testable Book');

        Sanctum::actingAs($user1, ['*']);

        // User 1 likes the book
        $this->postJson("/api/v1/books/{$book->id}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('meta.user_has_liked', true)
            ->assertJsonPath('meta.likes_count', 1);

        // Verify the like was created with correct user_id
        $like = BookLike::where('book_id', $book->id)->where('user_id', $user1->id)->first();
        $this->assertNotNull($like);
        $this->assertEquals($user1->id, $like->user_id);
        $this->assertEquals($book->id, $like->book_id);

        // User 1 can see they liked it
        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.user_has_liked', true);

        // User 2 likes the same book
        Sanctum::actingAs($user2, ['*']);

        $this->postJson("/api/v1/books/{$book->id}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('meta.user_has_liked', true)
            ->assertJsonPath('meta.likes_count', 2);

        // Verify user2's like has their user_id
        $like2 = BookLike::where('book_id', $book->id)->where('user_id', $user2->id)->first();
        $this->assertNotNull($like2);
        $this->assertEquals($user2->id, $like2->user_id);
        $this->assertNotEquals($like->user_id, $like2->user_id);

        // User 2 sees they liked it
        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.user_has_liked', true);

        // Verify total likes is 2
        $this->assertEquals(2, BookLike::where('book_id', $book->id)->count());
    }

    /**
     * Test that user can like and unlike books properly
     */
    public function test_book_like_toggle_removes_correctly(): void
    {
        $user = $this->createUser('user');
        $book = $this->createBook('Toggle Test Book');

        Sanctum::actingAs($user, ['*']);

        // Like the book
        $this->postJson("/api/v1/books/{$book->id}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true);

        $this->assertEquals(1, BookLike::where('book_id', $book->id)->count());

        // Unlike the book
        $this->postJson("/api/v1/books/{$book->id}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', false);

        $this->assertEquals(0, BookLike::where('book_id', $book->id)->count());

        // User can't see they liked it anymore
        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.user_has_liked', false);
    }

    /**
     * Test that user can like via the User relationship
     */
    public function test_user_has_book_likes_relationship(): void
    {
        $user = $this->createUser('user');
        $book1 = $this->createBook('Book 1');
        $book2 = $this->createBook('Book 2');

        Sanctum::actingAs($user, ['*']);

        // Like multiple books
        $this->postJson("/api/v1/books/{$book1->id}/like")->assertOk();
        $this->postJson("/api/v1/books/{$book2->id}/like")->assertOk();

        // Verify relationship works
        $user->refresh();
        $this->assertEquals(2, $user->bookLikes()->count());

        $likedBookIds = $user->bookLikes()
            ->pluck('book_id')
            ->sort()
            ->values()
            ->all();

        $expectedIds = collect([$book1->id, $book2->id])
            ->sort()
            ->values()
            ->all();

        $this->assertEquals($expectedIds, $likedBookIds);
    }

    /**
     * Test that add to playlist properly tracks user ownership via playlist.user_id
     */
    public function test_add_book_to_playlist_tracks_playlist_owner(): void
    {
        $owner = $this->createUser('user');
        $other = $this->createUser('user');
        $book = $this->createBook('Playlist Test Book');

        Sanctum::actingAs($owner, ['*']);

        // Owner creates a playlist
        $playlistRes = $this->postJson('/api/v1/playlists', [
            'name' => 'My Collection',
            'is_public' => true,
        ]);

        $playlistRes->assertCreated();
        $playlistId = $playlistRes->json('data.id');

        // Verify playlist is owned by the creator
        $playlist = Playlist::find($playlistId);
        $this->assertEquals($owner->id, $playlist->user_id);
        $this->assertTrue($playlist->isOwnedBy($owner));

        // Owner can add books
        $this->postJson("/api/v1/playlists/{$playlistId}/books", [
            'book_id' => $book->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.books_count', 1);

        // Verify the book was added to the correct playlist
        $this->assertTrue($playlist->books()->where('books.id', $book->id)->exists());

        // Other user cannot add books to this playlist
        Sanctum::actingAs($other, ['*']);

        $book2 = $this->createBook('Another Book');

        $this->postJson("/api/v1/playlists/{$playlistId}/books", [
            'book_id' => $book2->id,
        ])
            ->assertForbidden();

        // Verify the second book was NOT added
        $this->assertFalse($playlist->books()->where('books.id', $book2->id)->exists());
    }

    /**
     * Test playlist likes track correct user_id
     */
    public function test_playlist_like_tracks_correct_user_id(): void
    {
        $owner = $this->createUser('user');
        $user1 = $this->createUser('user');
        $user2 = $this->createUser('user');

        Sanctum::actingAs($owner, ['*']);

        // Create a public playlist
        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'Public Playlist',
            'is_public' => true,
        ])->json('data.id');

        // User 1 likes the playlist
        Sanctum::actingAs($user1, ['*']);

        $this->postJson("/api/v1/playlists/{$playlistId}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('meta.user_has_liked', true)
            ->assertJsonPath('meta.likes_count', 1);

        // Verify the like was created with correct user_id
        $like1 = PlaylistLike::where('playlist_id', $playlistId)
            ->where('user_id', $user1->id)
            ->first();
        $this->assertNotNull($like1);
        $this->assertEquals($user1->id, $like1->user_id);

        // User 2 likes the same playlist
        Sanctum::actingAs($user2, ['*']);

        $this->postJson("/api/v1/playlists/{$playlistId}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('meta.likes_count', 2);

        // Verify user2's like has their user_id
        $like2 = PlaylistLike::where('playlist_id', $playlistId)
            ->where('user_id', $user2->id)
            ->first();
        $this->assertNotNull($like2);
        $this->assertEquals($user2->id, $like2->user_id);
        $this->assertNotEquals($like1->user_id, $like2->user_id);

        // Verify total likes is 2
        $this->assertEquals(2, PlaylistLike::where('playlist_id', $playlistId)->count());
    }

    /**
     * Test that user can access their likes through the relationship
     */
    public function test_user_has_playlist_likes_relationship(): void
    {
        $owner = $this->createUser('user');
        $user = $this->createUser('user');

        Sanctum::actingAs($owner, ['*']);

        // Create two playlists
        $playlist1Id = $this->postJson('/api/v1/playlists', [
            'name' => 'Playlist 1',
            'is_public' => true,
        ])->json('data.id');

        $playlist2Id = $this->postJson('/api/v1/playlists', [
            'name' => 'Playlist 2',
            'is_public' => true,
        ])->json('data.id');

        // User likes both playlists
        Sanctum::actingAs($user, ['*']);

        $this->postJson("/api/v1/playlists/{$playlist1Id}/like")->assertOk();
        $this->postJson("/api/v1/playlists/{$playlist2Id}/like")->assertOk();

        // Verify relationship works
        $user->refresh();
        $this->assertEquals(2, $user->playlistLikes()->count());

        $likedPlaylistIds = $user->playlistLikes()
            ->pluck('playlist_id')
            ->sort()
            ->values()
            ->all();

        $expectedIds = collect([$playlist1Id, $playlist2Id])
            ->sort()
            ->values()
            ->all();

        $this->assertEquals($expectedIds, $likedPlaylistIds);
    }

    /**
     * Test full workflow: create playlist, add books, like, comment
     */
    public function test_full_playlist_workflow_with_user_tracking(): void
    {
        $owner = $this->createUser('user');
        $user1 = $this->createUser('user');
        $user2 = $this->createUser('user');

        $book1 = $this->createBook('Book 1');
        $book2 = $this->createBook('Book 2');

        // First test: verify that both users can like the same book
        Sanctum::actingAs($user1, ['*']);

        $resp1 = $this->postJson("/api/v1/books/{$book1->id}/like");
        $resp1->assertOk();
        $this->assertEquals(1, BookLike::where('book_id', $book1->id)->count());

        Sanctum::actingAs($user2, ['*']);

        $resp2 = $this->postJson("/api/v1/books/{$book1->id}/like");
        $resp2->assertOk();
        $this->assertEquals(2, BookLike::where('book_id', $book1->id)->count());

        Sanctum::actingAs($owner, ['*']);

        // Owner creates playlist with books
        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'Shared Reading List',
            'description' => 'Great books to read',
            'is_public' => true,
            'book_ids' => [$book1->id],
        ])->json('data.id');

        $playlist = Playlist::find($playlistId);
        $this->assertEquals($owner->id, $playlist->user_id);

        // Owner adds another book
        $this->postJson("/api/v1/playlists/{$playlistId}/books", [
            'book_id' => $book2->id,
        ])->assertCreated();

        // User 1 likes the playlist
        Sanctum::actingAs($user1, ['*']);

        $this->postJson("/api/v1/playlists/{$playlistId}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true);

        // User 1 likes book2
        $this->postJson("/api/v1/books/{$book2->id}/like")->assertOk();

        // User 2 also likes the playlist
        Sanctum::actingAs($user2, ['*']);

        $this->postJson("/api/v1/playlists/{$playlistId}/like")->assertOk();

        // Verify all relationships and tracking
        $this->assertEquals(2, PlaylistLike::where('playlist_id', $playlistId)->count());
        $this->assertEquals(2, BookLike::where('book_id', $book1->id)->count());
        $this->assertEquals(1, BookLike::where('book_id', $book2->id)->count());

        // Verify user relationships
        $user1->refresh();
        $user2->refresh();

        $this->assertEquals(1, $user1->playlistLikes()->count());
        $this->assertEquals(2, $user1->bookLikes()->count());

        $this->assertEquals(1, $user2->playlistLikes()->count());
        $this->assertEquals(1, $user2->bookLikes()->count());

        // Verify owner still owns the playlist
        $playlist->refresh();
        $this->assertEquals($owner->id, $playlist->user_id);
    }
}
