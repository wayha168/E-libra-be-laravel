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

        if ($book->relationLoaded('promotions')) {
            return $book->promotions
                ->filter(fn (Promotion $promotion) => $promotion->isCurrentlyActive())
                ->sortByDesc('discount_percent')
                ->first();
        }

        return $book->promotions()
            ->active()
            ->orderByDesc('discount_percent')
            ->first();
    }

    public static function effectivePrice(Books $book): ?float
    {
        if (is_null($book->price)) {
            return null;
        }

        $promotion = self::activePromotion($book);

        if (!$promotion) {
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
            'on_sale' => $promotion !== null,
        ];
    }
}
