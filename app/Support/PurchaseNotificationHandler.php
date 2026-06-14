<?php

namespace App\Support;

use App\Models\Author;
use App\Models\User;
use App\Models\UserBuyBook;

class PurchaseNotificationHandler
{
    public static function handle(UserBuyBook $purchase): void
    {
        $purchase->loadMissing(['user:id,name,email', 'book:id,title,author_id', 'book.author.user:id,name,email']);

        $buyer = $purchase->user;
        $book = $purchase->book;
        $status = $purchase->status;
        $amount = number_format((float) ($purchase->amount ?? 0), 2);

        $meta = [
            'purchase_id' => $purchase->id,
            'book_id' => $purchase->book_id,
            'book_title' => $book?->title,
            'status' => $status,
            'amount' => $purchase->amount,
        ];

        ActivityLogger::log(
            'purchase.' . $status,
            $status === 'paid' ? 'Book purchase completed' : 'New book order',
            $buyer && $book
                ? "{$buyer->name} — {$book->title} (\${$amount})"
                : null,
            $buyer,
            $buyer,
            $meta,
        );

        if (!$buyer || !$book) {
            return;
        }

        if ($status === 'pending') {
            NotificationService::send(
                $buyer,
                'purchase.pending',
                'Checkout started',
                "Your order for \"{$book->title}\" is pending payment.",
                $meta,
            );

            foreach (NotificationService::staffUsers() as $admin) {
                NotificationService::send(
                    $admin,
                    'purchase.order',
                    'New book order',
                    "{$buyer->name} ordered \"{$book->title}\" — pending payment.",
                    $meta,
                );
            }

            self::notifyAuthor($book->author_id, 'purchase.order', 'New sale on your book', "{$buyer->name} ordered \"{$book->title}\".", $meta);

            return;
        }

        if ($status === 'paid') {
            NotificationService::send(
                $buyer,
                'purchase.paid',
                'Purchase confirmed',
                "You now own \"{$book->title}\" — \${$amount} paid.",
                $meta,
            );

            foreach (NotificationService::staffUsers() as $admin) {
                NotificationService::send(
                    $admin,
                    'purchase.paid',
                    'Book sale completed',
                    "{$buyer->name} paid \${$amount} for \"{$book->title}\".",
                    $meta,
                );
            }

            self::notifyAuthor($book->author_id, 'purchase.paid', 'Book sale paid', "{$buyer->name} paid \${$amount} for \"{$book->title}\".", $meta);

            BookRecommendationService::notifyFromInteraction($buyer, $book, 'purchase');
        }
    }

    private static function notifyAuthor(?string $authorId, string $type, string $title, string $body, array $data): void
    {
        if (!$authorId) {
            return;
        }

        $author = Author::with('user')->find($authorId);
        if ($author?->user) {
            NotificationService::send($author->user, $type, $title, $body, $data);
        }
    }
}
