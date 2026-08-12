<?php

namespace Tests\Feature;

use App\Models\Books;
use App\Models\Permission;
use App\Models\Playlist;
use App\Models\PlaylistComment;
use App\Models\PlaylistLike;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlaylistSessionTest extends TestCase
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

    private function createBook(string $title = 'Session Book'): Books
    {
        return Books::create([
            'title' => $title,
            'description' => 'For playlist session tests',
            'price' => 0,
        ])->fresh();
    }

    private function permission(string $name, string $displayName): Permission
    {
        return Permission::firstOrCreate(
            ['name' => $name],
            [
                'display_name' => $displayName,
                'description' => $displayName,
            ]
        );
    }

    private function attachPermission(Role $role, Permission $permission): void
    {
        if (!$role->permissions()->where('permissions.id', $permission->id)->exists()) {
            $role->permissions()->attach($permission->id, ['id' => (string) Str::uuid()]);
        }
    }

    public function test_full_playlist_session_create_edit_books_like_comment_view_delete(): void
    {
        $owner = $this->createUser('user');
        $viewer = $this->createUser('user');
        $bookA = $this->createBook('Book A');
        $bookB = $this->createBook('Book B');
        $bookC = $this->createBook('Book C');

        Sanctum::actingAs($owner, ['*']);

        // Create playlist with one book
        $create = $this->postJson('/api/v1/playlists', [
            'name' => 'My Reading List',
            'description' => 'Weekend reads',
            'is_public' => true,
            'book_ids' => [$bookA->id],
        ]);

        $create->assertCreated()
            ->assertJsonPath('message', 'Playlist created successfully')
            ->assertJsonPath('data.name', 'My Reading List')
            ->assertJsonPath('data.books_count', 1)
            ->assertJsonPath('data.is_owner', true)
            ->assertJsonPath('data.can_edit', true);

        $playlistId = $create->json('data.id');
        $this->assertNotEmpty($playlistId);

        // Rename / edit
        $this->putJson("/api/v1/playlists/{$playlistId}", [
            'name' => 'Weekend Favorites',
            'description' => 'Updated description',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Weekend Favorites')
            ->assertJsonPath('data.description', 'Updated description');

        // Add more books
        $this->postJson("/api/v1/playlists/{$playlistId}/books", ['book_id' => $bookB->id])
            ->assertCreated()
            ->assertJsonPath('message', 'Book added to playlist')
            ->assertJsonPath('data.books_count', 2);

        $this->postJson("/api/v1/playlists/{$playlistId}/books", ['book_id' => $bookC->id])
            ->assertCreated()
            ->assertJsonPath('data.books_count', 3);

        // Duplicate add rejected
        $this->postJson("/api/v1/playlists/{$playlistId}/books", ['book_id' => $bookA->id])
            ->assertStatus(422);

        // Reorder books
        $this->putJson("/api/v1/playlists/{$playlistId}/books/reorder", [
            'book_ids' => [$bookC->id, $bookA->id, $bookB->id],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Playlist books reordered successfully');

        $orderedIds = collect($this->getJson("/api/v1/playlists/{$playlistId}")->json('data.books'))
            ->pluck('id')
            ->all();
        $this->assertSame([$bookC->id, $bookA->id, $bookB->id], $orderedIds);

        // Remove one book
        $this->deleteJson("/api/v1/playlists/{$playlistId}/books/{$bookA->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Book removed from playlist')
            ->assertJsonPath('data.books_count', 2);

        // My playlists
        $this->getJson('/api/v1/me/playlists')
            ->assertOk()
            ->assertJsonPath('message', 'Playlists fetched successfully');
        $this->assertTrue(
            collect($this->getJson('/api/v1/me/playlists')->json('data.data'))
                ->contains(fn ($row) => ($row['id'] ?? null) === $playlistId)
        );

        // Guest / other user can list public + view (view increments)
        Sanctum::actingAs($viewer, ['*']);

        $beforeViews = (int) Playlist::find($playlistId)->views_count;

        $show = $this->getJson("/api/v1/playlists/{$playlistId}");
        $show->assertOk()
            ->assertJsonPath('data.name', 'Weekend Favorites')
            ->assertJsonPath('data.views_count', $beforeViews + 1)
            ->assertJsonPath('data.is_owner', false)
            ->assertJsonPath('data.can_edit', false);

        // Like toggle
        $this->postJson("/api/v1/playlists/{$playlistId}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('meta.user_has_liked', true)
            ->assertJsonPath('meta.likes_count', 1);

        $this->postJson("/api/v1/playlists/{$playlistId}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('meta.user_has_liked', false)
            ->assertJsonPath('meta.likes_count', 0);

        $this->postJson("/api/v1/playlists/{$playlistId}/like")
            ->assertOk()
            ->assertJsonPath('data.liked', true);

        // Comment
        $this->postJson("/api/v1/playlists/{$playlistId}/comments", [
            'body' => 'Great collection!',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Comment added successfully')
            ->assertJsonPath('data.body', 'Great collection!')
            ->assertJsonPath('meta.comments_count', 1);

        // Public feedback / likes / comments
        $this->getJson("/api/v1/playlists/{$playlistId}/feedback")
            ->assertOk()
            ->assertJsonPath('meta.likes_count', 1)
            ->assertJsonPath('meta.comments_count', 1)
            ->assertJsonPath('meta.user_has_liked', true);

        $this->getJson("/api/v1/playlists/{$playlistId}/likes")
            ->assertOk()
            ->assertJsonPath('meta.likes_count', 1);

        $this->getJson("/api/v1/playlists/{$playlistId}/comments")
            ->assertOk()
            ->assertJsonPath('meta.comments_count', 1);

        // Non-owner cannot edit/delete
        $this->putJson("/api/v1/playlists/{$playlistId}", ['name' => 'Hacked'])
            ->assertForbidden();

        $this->deleteJson("/api/v1/playlists/{$playlistId}")
            ->assertForbidden();

        // Owner deletes
        Sanctum::actingAs($owner, ['*']);
        $this->deleteJson("/api/v1/playlists/{$playlistId}")
            ->assertOk()
            ->assertJsonPath('message', 'Playlist deleted successfully');

        $this->assertNull(Playlist::find($playlistId));
        $this->assertSame(0, PlaylistLike::where('playlist_id', $playlistId)->count());
        $this->assertSame(0, PlaylistComment::where('playlist_id', $playlistId)->count());
    }

    public function test_private_playlist_is_hidden_from_others_and_visible_to_owner(): void
    {
        $owner = $this->createUser('user');
        $other = $this->createUser('user');

        Sanctum::actingAs($owner, ['*']);

        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'Secret Shelf',
            'is_public' => false,
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/v1/playlists/{$playlistId}")
            ->assertOk()
            ->assertJsonPath('data.is_public', false);

        Sanctum::actingAs($other, ['*']);

        $this->getJson("/api/v1/playlists/{$playlistId}")
            ->assertNotFound();

        $this->postJson("/api/v1/playlists/{$playlistId}/like")
            ->assertNotFound();

        $this->postJson("/api/v1/playlists/{$playlistId}/comments", ['body' => 'Nope'])
            ->assertNotFound();
    }

    public function test_guest_can_browse_public_playlists_without_auth(): void
    {
        $owner = $this->createUser('user');
        Sanctum::actingAs($owner, ['*']);

        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'Public Shelf',
            'is_public' => true,
        ])->assertCreated()->json('data.id');

        // Clear auth for guest requests
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/playlists')
            ->assertOk()
            ->assertJsonPath('message', 'Playlists fetched successfully');

        $this->getJson("/api/v1/playlists/{$playlistId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Public Shelf');

        $this->getJson("/api/v1/playlists/{$playlistId}/feedback")
            ->assertOk();
    }

    public function test_role_permission_sync_grants_and_revokes_edit_playlist_without_create(): void
    {
        $admin = $this->createUser('admin');
        $staffRole = $this->role('author');
        $staff = $this->createUser('author');
        $owner = $this->createUser('user');

        $editPerm = $this->permission('edit_playlists', 'Edit Playlists');
        $viewPerm = $this->permission('view_playlists', 'View Playlists');
        $managePerm = $this->permission('manage_permissions', 'Manage Permissions');

        // Admin needs manage path via role middleware only; attach manage for completeness
        $this->attachPermission($this->role('admin'), $managePerm);

        Sanctum::actingAs($owner, ['*']);
        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'Owner List',
            'is_public' => true,
        ])->assertCreated()->json('data.id');

        // Author without edit_playlists cannot edit someone else's playlist
        Sanctum::actingAs($staff, ['*']);
        $this->putJson("/api/v1/playlists/{$playlistId}", ['name' => 'Staff Edit'])
            ->assertForbidden();

        // Admin syncs permissions onto author role (grant edit)
        Sanctum::actingAs($admin, ['*']);

        // Create permission endpoint removed
        $this->postJson('/api/v1/permissions', [
            'display_name' => 'Should Not Create',
        ])->assertStatus(405);

        $this->putJson("/api/v1/roles/{$staffRole->id}/permissions", [
            'permission_ids' => [$viewPerm->id, $editPerm->id],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Role permissions updated successfully');

        $this->assertTrue($staff->fresh()->hasPermission('edit_playlists'));

        Sanctum::actingAs($staff->fresh(), ['*']);
        $this->putJson("/api/v1/playlists/{$playlistId}", ['name' => 'Staff Edit'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Staff Edit');

        // Revoke edit by syncing without edit_playlists
        Sanctum::actingAs($admin, ['*']);
        $this->putJson("/api/v1/roles/{$staffRole->id}/permissions", [
            'permission_ids' => [$viewPerm->id],
        ])->assertOk();

        $this->assertFalse($staff->fresh()->hasPermission('edit_playlists'));

        Sanctum::actingAs($staff->fresh(), ['*']);
        $this->putJson("/api/v1/playlists/{$playlistId}", ['name' => 'Should Fail'])
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_mutate_playlists(): void
    {
        $owner = $this->createUser('user');
        Sanctum::actingAs($owner, ['*']);
        $playlistId = $this->postJson('/api/v1/playlists', [
            'name' => 'Locked',
            'is_public' => true,
        ])->assertCreated()->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/playlists', ['name' => 'Nope'])
            ->assertUnauthorized();

        $this->putJson("/api/v1/playlists/{$playlistId}", ['name' => 'Nope'])
            ->assertUnauthorized();

        $this->postJson("/api/v1/playlists/{$playlistId}/like")
            ->assertUnauthorized();

        $this->postJson("/api/v1/playlists/{$playlistId}/comments", ['body' => 'Hi'])
            ->assertUnauthorized();
    }
}
