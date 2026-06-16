<?php

namespace App\Policies;

use App\Models\Books;
use App\Models\User;

class BooksPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->hasPermission('view_books');
    }

    public function view(User $user, Books $books): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->isAuthor() && $user->authorProfile?->id === $books->author_id) {
            return true;
        }

        return $user->hasPermission('view_books');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->hasPermission('create_books');
    }

    public function update(User $user, Books $books): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->isAuthor() && $user->authorProfile?->id === $books->author_id) {
            return $user->hasPermission('edit_books');
        }

        return false;
    }

    public function delete(User $user, Books $books): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->isAuthor() && $user->authorProfile?->id === $books->author_id) {
            return $user->hasPermission('delete_books');
        }

        return false;
    }

    public function restore(User $user, Books $books): bool
    {
        return $this->update($user, $books);
    }

    public function forceDelete(User $user, Books $books): bool
    {
        return $this->delete($user, $books);
    }
}
