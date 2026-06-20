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

        $activityLabels = [
            'paid' => 'Book purchase completed',
            'pending' => 'New book order',
            'failed' => 'Book payment failed',
            'canceled' => 'Book order canceled',
        ];

        ActivityLogger::log(
            'purchase.' . $status,
            $activityLabels[$status] ?? 'Book order update',
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

            TelegramNotifier::sendPurchasePaid($purchase);

            return;
        }

        if ($status === 'failed' || $status === 'canceled') {
            $isFailed = $status === 'failed';

            NotificationService::send(
                $buyer,
                'purchase.' . $status,
                $isFailed ? 'Payment failed' : 'Order canceled',
                $isFailed
                    ? "Your payment for \"{$book->title}\" could not be processed. Please try again."
                    : "Your order for \"{$book->title}\" was canceled.",
                $meta,
            );

            foreach (NotificationService::staffUsers() as $admin) {
                NotificationService::send(
                    $admin,
                    'purchase.' . $status,
                    $isFailed ? 'Book payment failed' : 'Book order canceled',
                    $isFailed
                        ? "{$buyer->name}'s payment for \"{$book->title}\" failed."
                        : "{$buyer->name}'s order for \"{$book->title}\" was canceled.",
                    $meta,
                );
            }

            self::notifyAuthor(
                $book->author_id,
                'purchase.' . $status,
                $isFailed ? 'Sale payment failed' : 'Sale canceled',
                $isFailed
                    ? "{$buyer->name}'s payment for \"{$book->title}\" failed."
                    : "{$buyer->name}'s order for \"{$book->title}\" was canceled.",
                $meta,
            );
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
