<?php

namespace App\Support;

use App\Models\Books;
use App\Models\Promotion;

class BookPricing
{
    public static function activePromotion(Books $book): ?Promotion
    {
        if (is_null($book->price) || $book->price <= 0) {
            return null;
        }

        $candidates = collect();

        if ($book->relationLoaded('promotions')) {
            $candidates = $candidates->merge(
                $book->promotions->filter(
                    fn (Promotion $promotion) => $promotion->isPercentage() && $promotion->isCurrentlyActive()
                )
            );
        } else {
            $candidates = $candidates->merge(
                $book->promotions()
                    ->percentage()
                    ->active()
                    ->get()
            );
        }

        if ($book->author_id) {
            $candidates = $candidates->merge(
                Promotion::query()
                    ->percentage()
                    ->active()
                    ->where('author_id', $book->author_id)
                    ->whereNull('book_id')
                    ->get()
            );
        }

        return $candidates
            ->unique('id')
            ->sortByDesc('discount_percent')
            ->first();
    }

    public static function effectivePrice(Books $book): ?float
    {
        if (is_null($book->price)) {
            return null;
        }

        $promotion = self::activePromotion($book);

        if (! $promotion || ! $promotion->discount_percent) {
            return round((float) $book->price, 2);
        }

        $discounted = (float) $book->price * (1 - $promotion->discount_percent / 100);

        return round(max(0, $discounted), 2);
    }

    public static function discountMeta(Books $book): array
    {
        $original = is_null($book->price) ? null : round((float) $book->price, 2);
        $promotion = self::activePromotion($book);
        $effective = self::effectivePrice($book);

        return [
            'original_price' => $original,
            'effective_price' => $effective,
            'discount_percent' => $promotion?->discount_percent,
            'on_sale' => $promotion !== null && $promotion->isPercentage(),
            'promotion_scope' => $promotion
                ? ($promotion->author_id && ! $promotion->book_id ? 'author' : 'book')
                : null,
        ];
    }
}
