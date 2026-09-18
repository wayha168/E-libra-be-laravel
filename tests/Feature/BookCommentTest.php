<?php

namespace Tests\Feature;

use App\Models\BookComment;
use App\Models\Books;
use App\Models\Role;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookCommentTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        $role = Role::firstOrCreate(['role' => 'user']);

        return User::create([
            'name' => 'Commenter',
            'email' => 'commenter-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ])->fresh();
    }

    private function createBook(): Books
    {
        return Books::create([
            'title' => 'Commentable Book',
            'description' => 'x',
            'price' => 0,
        ])->fresh();
    }

    public function test_user_can_comment_on_a_book(): void
    {
        $user = $this->createUser();
        $book = $this->createBook();

        Sanctum::actingAs($user, ['*']);

        $this->postJson("/api/v1/books/{$book->id}/comments", ['body' => 'Great read!'])
            ->assertCreated()
            ->assertJsonPath('message', 'Comment added successfully')
            ->assertJsonPath('data.body', 'Great read!')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('meta.comments_count', 1);

        $this->assertDatabaseHas('book_comments', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'body' => 'Great read!',
        ]);

        // Comment is recorded as user activity (audit).
        $this->assertSame(1, UserActivity::where('type', 'book.commented')->count());
    }

    public function test_comment_requires_a_body(): void
    {
        $user = $this->createUser();
        $book = $this->createBook();

        Sanctum::actingAs($user, ['*']);

        $this->postJson("/api/v1/books/{$book->id}/comments", ['body' => ''])
            ->assertStatus(422);
    }

    public function test_public_can_list_book_comments(): void
    {
        $user = $this->createUser();
        $book = $this->createBook();
        BookComment::create(['user_id' => $user->id, 'book_id' => $book->id, 'body' => 'Nice']);

        $this->getJson("/api/v1/books/{$book->id}/comments")
            ->assertOk()
            ->assertJsonPath('meta.comments_count', 1)
            ->assertJsonPath('data.data.0.body', 'Nice');
    }
}
