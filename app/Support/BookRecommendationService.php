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

/**
 * Personalized book SEO / discovery recommendations.
 *
 * When a user has paid purchases, candidates are drawn from:
 * - the same categories as bought books, or
 * - the same authors as bought books.
 * Likes/comments only seed signals when the user has no purchases yet.
 */
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
            ->limit(max($limit * 5, 30))
            ->get()
            ->map(fn (Books $book) => self::scoreCandidate($book, $signals))
            ->sortByDesc(fn (Books $book) => [
                $book->recommendation_score,
                $book->paid_purchases_count ?? 0,
                strtotime((string) $book->created_at) ?: 0,
            ])
            ->take($limit)
            ->values();

        if ($candidates->count() >= $limit) {
            return $candidates;
        }

        // Fill remaining slots with popular titles the user has not seen
        $filler = self::popularBooks(
            $limit - $candidates->count(),
            $signals['exclude_ids']->merge($candidates->pluck('id'))
        );

        return $candidates->concat($filler)->values();
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

            $sameCategory = $sourceBook->category_id && $book->category_id === $sourceBook->category_id;
            $sameAuthor = $sourceBook->author_id && $book->author_id === $sourceBook->author_id;
            $authorName = $book->author?->user?->name;
            $categoryName = $book->category?->name;

            $reason = match (true) {
                $trigger === 'purchase' && $sameAuthor && $authorName => "More from {$authorName} — you bought \"{$sourceBook->title}\"",
                $trigger === 'purchase' && $sameCategory && $categoryName => "More in {$categoryName} — you bought \"{$sourceBook->title}\"",
                $trigger === 'purchase' => "Because you bought \"{$sourceBook->title}\"",
                $trigger === 'like' => "Because you liked \"{$sourceBook->title}\"",
                $trigger === 'comment' => "Because you reviewed \"{$sourceBook->title}\"",
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
                    'author_id' => $book->author_id,
                    'source_book_id' => $sourceBook->id,
                    'trigger' => $trigger,
                    'match' => $sameAuthor ? 'author' : ($sameCategory ? 'category' : 'related'),
                ],
            );
        }
    }

    public static function notifyInterestedUsers(Books $book): void
    {
        if (! $book->category_id && ! $book->author_id) {
            return;
        }

        $userIds = collect();

        if ($book->category_id) {
            $categoryBookIds = Books::where('category_id', $book->category_id)
                ->where('id', '!=', $book->id)
                ->pluck('id');

            // Prefer buyers in this category (SEO from purchase history)
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

            $boughtSameAuthor = $book->author_id && UserBuyBook::query()
                ->where('user_id', $user->id)
                ->where('status', 'paid')
                ->whereHas('book', fn ($q) => $q->where('author_id', $book->author_id))
                ->exists();

            $authorName = $book->author?->user?->name;
            $categoryName = $book->category?->name ?? 'your favorite genre';

            $body = $boughtSameAuthor && $authorName
                ? "\"{$book->title}\" is a new release from {$authorName}."
                : "\"{$book->title}\" was just added in {$categoryName}.";

            NotificationService::send(
                $user,
                'recommendation.new',
                'New book you may like',
                $body,
                [
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                    'category_id' => $book->category_id,
                    'author_id' => $book->author_id,
                ],
            );
        }
    }

    private static function scoreCandidate(Books $book, array $signals): Books
    {
        $score = (int) ($book->paid_purchases_count ?? 0);
        $reasons = [];
        $fromPurchase = (bool) ($signals['from_purchases'] ?? false);

        $sameAuthor = $signals['author_ids']->contains($book->author_id);
        $sameCategory = $signals['category_ids']->contains($book->category_id);

        if ($sameAuthor) {
            // Author match from bought books ranks highest
            $score += $fromPurchase ? 12 : 5;
            $authorName = $book->author?->user?->name ?? 'this author';
            $reasons[] = $fromPurchase
                ? "More from {$authorName} — based on books you bought"
                : "More from {$authorName}";
        }

        if ($sameCategory) {
            $score += $fromPurchase ? 8 : 3;
            $categoryName = $book->category?->name;
            $reasons[] = $fromPurchase && $categoryName
                ? "Because you bought books in {$categoryName}"
                : ($categoryName ? "Because you enjoy {$categoryName}" : 'Based on categories you read');
        }

        if ($book->paid_purchases_count > 0) {
            $score += 1;
            $reasons[] = self::popularReason((int) $book->paid_purchases_count);
        }

        $book->recommendation_score = $score;
        $book->recommendation_reason = $reasons[0] ?? self::popularReason((int) $book->paid_purchases_count);

        return $book;
    }

    private static function similarTo(User $user, Books $sourceBook, int $limit): Collection
    {
        $signals = self::interestSignals($user);

        // Always relate to the source book's category OR author
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
            ->get()
            ->map(function (Books $book) use ($sourceBook) {
                $sameAuthor = $sourceBook->author_id && $book->author_id === $sourceBook->author_id;
                $sameCategory = $sourceBook->category_id && $book->category_id === $sourceBook->category_id;
                $score = (int) ($book->paid_purchases_count ?? 0);
                $score += $sameAuthor ? 12 : 0;
                $score += $sameCategory ? 8 : 0;

                return self::decorate($book, $book->recommendation_reason ?? 'Recommended for you', $score);
            })
            ->sortByDesc(fn (Books $book) => $book->recommendation_score)
            ->values();
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

        // Never recommend books the user already bought / engaged with
        $excludeIds = $purchasedIds->merge($likedIds)->merge($commentedIds)->unique()->filter()->values();

        // Purchase-first SEO: category + author from bought books
        if ($purchasedIds->isNotEmpty()) {
            $boughtBooks = Books::whereIn('id', $purchasedIds)->get(['id', 'category_id', 'author_id']);

            return [
                'exclude_ids' => $excludeIds,
                'purchased_ids' => $purchasedIds,
                'from_purchases' => true,
                'category_ids' => $boughtBooks->pluck('category_id')->filter()->unique()->values(),
                'author_ids' => $boughtBooks->pluck('author_id')->filter()->unique()->values(),
            ];
        }

        // Cold start: fall back to likes/comments until the user buys something
        $seedIds = $likedIds->merge($commentedIds)->unique()->filter();
        $seedBooks = Books::whereIn('id', $seedIds)->get(['id', 'category_id', 'author_id']);

        return [
            'exclude_ids' => $excludeIds,
            'purchased_ids' => $purchasedIds,
            'from_purchases' => false,
            'category_ids' => $seedBooks->pluck('category_id')->filter()->unique()->values(),
            'author_ids' => $seedBooks->pluck('author_id')->filter()->unique()->values(),
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
