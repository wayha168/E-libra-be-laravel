<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorCreatedByTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['role' => 'admin']);

        return User::create([
            'name' => 'Creator Admin',
            'email' => 'admin-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ])->fresh();
    }

    public function test_creating_an_author_records_the_creator(): void
    {
        Role::firstOrCreate(['role' => 'author']);
        $admin = $this->makeAdmin();

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/v1/authors', [
            'mode' => 'new_account',
            'name' => 'Fresh Author',
            'email' => 'fresh-author@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'bio' => 'Writes about Laravel.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.created_by', $admin->id)
            ->assertJsonPath('data.created_by_name', $admin->name);

        $author = Author::firstOrFail();
        $this->assertSame($admin->id, $author->created_by);

        // Audit trail records who created the author.
        $this->assertDatabaseHas('user_activities', [
            'type' => 'author.created',
            'actor_id' => $admin->id,
        ]);

        // The author's own user is different from the creator.
        $this->assertNotSame($author->user_id, $author->created_by);
    }

    public function test_author_show_endpoint_exposes_creator_name(): void
    {
        Role::firstOrCreate(['role' => 'author']);
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin, ['*']);

        $authorId = $this->postJson('/api/v1/authors', [
            'mode' => 'new_account',
            'name' => 'Shown Author',
            'email' => 'shown-author@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/v1/authors/{$authorId}")
            ->assertOk()
            ->assertJsonPath('data.created_by', $admin->id)
            ->assertJsonPath('data.created_by_name', $admin->name);
    }
}
