<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\Books;
use App\Models\User;

class BookReleaseNotificationHandler
{
    public static function handle(Books $book): void
    {
        $book->loadMissing(['category', 'author.user']);

        $authorName = $book->author?->user?->name;
        $title = 'New book released';
        $body = $authorName
            ? "\"{$book->title}\" by {$authorName} is now available."
            : "\"{$book->title}\" is now available on e-Libra.";

        $meta = [
            'book_id' => $book->id,
            'book_title' => $book->title,
            'author_id' => $book->author_id,
            'category_id' => $book->category_id,
        ];

        User::query()
            ->whereHas('role', fn ($q) => $q->where('role', 'user'))
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($book, $title, $body, $meta) {
                foreach ($users as $user) {
                    if (self::alreadyNotified($user->id, $book->id)) {
                        continue;
                    }

                    NotificationService::send($user, 'book.released', $title, $body, $meta);
                }
            });
    }

    private static function alreadyNotified(string $userId, string $bookId): bool
    {
        return AppNotification::query()
            ->where('user_id', $userId)
            ->where('type', 'book.released')
            ->where('data->book_id', $bookId)
            ->exists();
    }
}
