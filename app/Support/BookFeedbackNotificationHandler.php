<?php

namespace App\Support;

use App\Models\Author;
use App\Models\BookComment;
use App\Models\Books;
use App\Models\User;
use Illuminate\Support\Str;

class BookFeedbackNotificationHandler
{
    public static function handleLike(User $actor, Books $book): void
    {
        $book->loadMissing(['author.user']);

        $meta = self::meta($book, $actor);

        ActivityLogger::log(
            'book.liked',
            'Book liked',
            "{$actor->name} liked \"{$book->title}\"",
            null,
            $actor,
            $meta,
        );

        self::notifyRecipients(
            $book,
            $actor,
            'book.like',
            'New like on your book',
            "{$actor->name} liked \"{$book->title}\".",
            $meta,
        );
    }

    public static function handleComment(User $actor, Books $book, BookComment $comment): void
    {
        $book->loadMissing(['author.user']);

        $preview = Str::limit($comment->body, 120);
        $meta = array_merge(self::meta($book, $actor), [
            'comment_id' => $comment->id,
            'comment_preview' => $preview,
        ]);

        ActivityLogger::log(
            'book.commented',
            'New book comment',
            "{$actor->name} on \"{$book->title}\": {$preview}",
            null,
            $actor,
            $meta,
        );

        self::notifyRecipients(
            $book,
            $actor,
            'book.comment',
            'New comment on your book',
            "{$actor->name} commented on \"{$book->title}\": {$preview}",
            $meta,
        );
    }

    private static function meta(Books $book, User $actor): array
    {
        return [
            'book_id' => $book->id,
            'book_title' => $book->title,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
        ];
    }

    private static function notifyRecipients(
        Books $book,
        User $actor,
        string $type,
        string $title,
        string $body,
        array $data,
    ): void {
        $recipients = collect();

        if ($book->author_id) {
            $author = Author::with('user')->find($book->author_id);
            if ($author?->user && $author->user->id !== $actor->id) {
                $recipients->push($author->user);
            }
        }

        foreach (NotificationService::staffUsers() as $admin) {
            if ($admin->id !== $actor->id) {
                $recipients->push($admin);
            }
        }

        $recipients->unique('id')->each(
            fn (User $user) => NotificationService::send($user, $type, $title, $body, $data)
        );
    }
}
