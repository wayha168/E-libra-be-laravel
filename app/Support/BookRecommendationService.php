<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\BookComment;
use App\Models\BookLike;
use App\Models\Books;
use App\Models\User;
use App\Models\UserBuyBook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BookRecommendationService
{
    public static function forUser(User $user, int $limit = 12): Collection
    {
        $signals = self::interestSignals($user);

        if ($signals['category_ids']->isEmpty() && $signals['author_ids']->isEmpty()) {
            return self::popularBooks($limit, $signals['exclude_ids']);
        }

        $candidates = self::booksWithPurchaseCount()
            ->whereNotIn('id', $signals['exclude_ids'])
            ->where(function ($q) use ($signals) {
                if ($signals['category_ids']->isNotEmpty()) {
                    $q->whereIn('category_id', $signals['category_ids']);
                }
                if ($signals['author_ids']->isNotEmpty()) {
                    $q->orWhereIn('author_id', $signals['author_ids']);
                }
            })
            ->limit($limit * 3)
            ->get();

        return $candidates
            ->map(function (Books $book) use ($signals) {
                $score = (int) ($book->paid_purchases_count ?? 0);
                $reasons = [];

                if ($signals['category_ids']->contains($book->category_id)) {
                    $score += 2;
                    $reasons[] = $book->category?->name
                        ? "Because you enjoy {$book->category->name}"
                        : 'Based on categories you read';
                }

                if ($signals['author_ids']->contains($book->author_id)) {
                    $score += 3;
                    $authorName = $book->author?->user?->name ?? 'this author';
                    $reasons[] = "More from {$authorName}";
                }

                if ($book->paid_purchases_count > 0) {
                    $reasons[] = self::popularReason((int) $book->paid_purchases_count);
                }

                $book->recommendation_score = $score;
                $book->recommendation_reason = $reasons[0] ?? self::popularReason((int) $book->paid_purchases_count);

                return $book;
            })
            ->sortByDesc(fn (Books $book) => [$book->recommendation_score, $book->paid_purchases_count ?? 0])
            ->take($limit)
            ->values();
    }

    public static function popularBooks(int $limit = 12, Collection|array $excludeIds = []): Collection
    {
        $exclude = collect($excludeIds)->filter()->values();

        return self::booksWithPurchaseCount()
            ->when($exclude->isNotEmpty(), fn (Builder $q) => $q->whereNotIn('id', $exclude))
            ->orderByDesc('paid_purchases_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Books $book) {
                $count = (int) ($book->paid_purchases_count ?? 0);

                return self::decorate(
                    $book,
                    $count > 0 ? self::popularReason($count) : 'Popular on e-Libra',
                    $count,
                );
            });
    }

    public static function notifyFromInteraction(User $user, Books $sourceBook, string $trigger): void
    {
        $recs = self::similarTo($user, $sourceBook, 3);

        foreach ($recs as $book) {
            if (self::alreadyNotified($user, 'recommendation.book', $book->id)) {
                continue;
            }

            $reason = match ($trigger) {
                'purchase' => "Because you bought \"{$sourceBook->title}\"",
                'like' => "Because you liked \"{$sourceBook->title}\"",
                'comment' => "Because you reviewed \"{$sourceBook->title}\"",
                default => 'Based on your reading history',
            };

            NotificationService::send(
                $user,
                'recommendation.book',
                'You might like this book',
                "\"{$book->title}\" — {$reason}",
                [
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                    'category_id' => $book->category_id,
                    'source_book_id' => $sourceBook->id,
                    'trigger' => $trigger,
                ],
            );
        }
    }

    public static function notifyInterestedUsers(Books $book): void
    {
        if (!$book->category_id && !$book->author_id) {
            return;
        }

        $userIds = collect();

        if ($book->category_id) {
            $categoryBookIds = Books::where('category_id', $book->category_id)
                ->where('id', '!=', $book->id)
                ->pluck('id');

            $userIds = $userIds->merge(
                UserBuyBook::whereIn('book_id', $categoryBookIds)->where('status', 'paid')->pluck('user_id')
            )->merge(
                BookLike::whereIn('book_id', $categoryBookIds)->pluck('user_id')
            )->merge(
                BookComment::whereIn('book_id', $categoryBookIds)->pluck('user_id')
            );
        }

        if ($book->author_id) {
            $authorBookIds = Books::where('author_id', $book->author_id)
                ->where('id', '!=', $book->id)
                ->pluck('id');

            $userIds = $userIds->merge(
                UserBuyBook::whereIn('book_id', $authorBookIds)->where('status', 'paid')->pluck('user_id')
            )->merge(
                BookLike::whereIn('book_id', $authorBookIds)->pluck('user_id')
            )->merge(
                BookComment::whereIn('book_id', $authorBookIds)->pluck('user_id')
            );
        }

        $users = User::whereIn('id', $userIds->unique())->get();

        foreach ($users as $user) {
            if (self::alreadyNotified($user, 'recommendation.new', $book->id)) {
                continue;
            }

            $categoryName = $book->category?->name ?? 'your favorite genre';

            NotificationService::send(
                $user,
                'recommendation.new',
                'New book you may like',
                "\"{$book->title}\" was just added in {$categoryName}.",
                [
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                    'category_id' => $book->category_id,
                ],
            );
        }
    }

    private static function similarTo(User $user, Books $sourceBook, int $limit): Collection
    {
        $signals = self::interestSignals($user);

        return self::booksWithPurchaseCount()
            ->where('id', '!=', $sourceBook->id)
            ->whereNotIn('id', $signals['exclude_ids'])
            ->where(function ($q) use ($sourceBook) {
                if ($sourceBook->category_id) {
                    $q->where('category_id', $sourceBook->category_id);
                }
                if ($sourceBook->author_id) {
                    $q->orWhere('author_id', $sourceBook->author_id);
                }
            })
            ->orderByDesc('paid_purchases_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private static function booksWithPurchaseCount(): Builder
    {
        return Books::query()
            ->with(['category', 'author.user'])
            ->withCount([
                'purchases as paid_purchases_count' => fn (Builder $q) => $q->where('status', 'paid'),
            ]);
    }

    private static function popularReason(int $count): string
    {
        return $count === 1
            ? '1 purchase — popular on e-Libra'
            : "{$count} purchases — popular on e-Libra";
    }

    private static function interestSignals(User $user): array
    {
        $purchasedIds = UserBuyBook::where('user_id', $user->id)
            ->where('status', 'paid')
            ->pluck('book_id');

        $likedIds = BookLike::where('user_id', $user->id)->pluck('book_id');
        $commentedIds = BookComment::where('user_id', $user->id)->pluck('book_id');

        $interactedIds = $purchasedIds->merge($likedIds)->merge($commentedIds)->unique()->filter();
        $excludeIds = $interactedIds;

        $interactedBooks = Books::whereIn('id', $interactedIds)->get(['id', 'category_id', 'author_id']);

        return [
            'exclude_ids' => $excludeIds,
            'purchased_ids' => $purchasedIds,
            'category_ids' => $interactedBooks->pluck('category_id')->filter()->unique()->values(),
            'author_ids' => $interactedBooks->pluck('author_id')->filter()->unique()->values(),
        ];
    }

    private static function decorate(Books $book, string $reason, int $purchaseCount = 0): Books
    {
        $book->recommendation_score = $purchaseCount;
        $book->recommendation_reason = $reason;

        return $book;
    }

    private static function alreadyNotified(User $user, string $type, string $bookId): bool
    {
        return AppNotification::where('user_id', $user->id)
            ->where('type', $type)
            ->where('data->book_id', $bookId)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();
    }
}
