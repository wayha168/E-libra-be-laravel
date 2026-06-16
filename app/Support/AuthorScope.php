<?php

namespace App\Support;

use App\Models\Books;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AuthorScope
{
    public static function isAuthorOnly(User $user): bool
    {
        return $user->isAuthor() && ! $user->isAdmin() && ! $user->isSuperAdmin();
    }

    public static function authorId(User $user): ?string
    {
        return $user->authorProfile?->id;
    }

    public static function authorIdOrAbort(User $user): string
    {
        $authorId = self::authorId($user);

        if (! $authorId) {
            abort(403, 'No author profile is linked to your account. Ask an admin to set this up.');
        }

        return $authorId;
    }

    public static function ensureOwnsBook(User $user, Books $book): void
    {
        if (! self::isAuthorOnly($user)) {
            return;
        }

        if ($book->author_id !== self::authorIdOrAbort($user)) {
            abort(403, 'You can only manage your own books.');
        }
    }

    public static function scopePurchasesToAuthorBooks(Builder $query, User $user): Builder
    {
        if (! self::isAuthorOnly($user)) {
            return $query;
        }

        $bookIds = $user->authorProfile?->books()->pluck('id') ?? collect();

        if ($bookIds->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('book_id', $bookIds);
    }
}
