<?php

namespace App\Support;

use App\Models\Author;
use App\Models\User;

/**
 * Records author-profile creation as an activity (so it shows in the audit
 * page, including who created it) and notifies staff + the new author.
 */
class AuthorNotificationHandler
{
    public static function created(Author $author, ?User $creator = null): void
    {
        $author->loadMissing('user:id,name,email');
        $authorUser = $author->user;
        $creatorName = $creator?->name ?? 'System';

        $meta = [
            'author_id' => $author->id,
            'user_id' => $author->user_id,
            'created_by' => $creator?->id,
        ];

        ActivityLogger::log(
            'author.created',
            'Author created',
            $authorUser
                ? "{$creatorName} created author {$authorUser->name} ({$authorUser->email})"
                : "{$creatorName} created a new author",
            $authorUser,
            $creator,
            $meta,
        );

        if ($authorUser) {
            NotificationService::send(
                $authorUser,
                'author.created',
                'You are now an author',
                "Your author profile has been set up. You can start publishing books.",
                $meta,
            );
        }

        foreach (NotificationService::staffUsers() as $admin) {
            if ($authorUser && $admin->id === $authorUser->id) {
                continue;
            }

            NotificationService::send(
                $admin,
                'author.created',
                'New author added',
                $authorUser
                    ? "{$creatorName} created author {$authorUser->name}."
                    : "{$creatorName} created a new author.",
                $meta,
            );
        }
    }
}
