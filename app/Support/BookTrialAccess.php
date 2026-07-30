<?php

namespace App\Support;

use App\Models\Books;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserAccessTrial;

class BookTrialAccess
{
    public static function activeFreeTrialPromotion(Books $book): ?Promotion
    {
        if (! BookAccess::isPaid($book)) {
            return null;
        }

        return Promotion::query()
            ->freeTrial()
            ->active()
            ->where(function ($q) use ($book) {
                $q->where('book_id', $book->id);
                if ($book->author_id) {
                    $q->orWhere('author_id', $book->author_id);
                }
            })
            ->latest()
            ->first();
    }

    public static function coveringTrialsQuery(User $user, Books $book)
    {
        return UserAccessTrial::query()
            ->where('user_id', $user->id)
            ->whereHas('promotion', function ($q) use ($book) {
                $q->where('type', Promotion::TYPE_FREE_TRIAL)
                    ->where(function ($inner) use ($book) {
                        $inner->where('book_id', $book->id);
                        if ($book->author_id) {
                            $inner->orWhere('author_id', $book->author_id);
                        }
                    });
            });
    }

    public static function trialFor(User $user, Books $book): ?UserAccessTrial
    {
        return self::coveringTrialsQuery($user, $book)
            ->latest('ends_at')
            ->first();
    }

    public static function hasActiveTrial(User $user, Books $book): bool
    {
        return self::coveringTrialsQuery($user, $book)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->exists();
    }

    public static function hasExpiredTrial(User $user, Books $book): bool
    {
        $trial = self::trialFor($user, $book);

        return $trial?->isExpired() === true;
    }

    /**
     * Start a free-trial claim when the user requests access (once per promotion).
     */
    public static function claim(User $user, Books $book): array
    {
        $promo = self::activeFreeTrialPromotion($book);

        $existing = self::trialFor($user, $book);

        if ($existing) {
            if ($existing->isActive()) {
                return [
                    'ok' => true,
                    'status' => 200,
                    'code' => 'trial_active',
                    'message' => 'Your free trial is active.',
                    'trial' => $existing,
                    'promotion' => $existing->promotion,
                    'payment_required' => false,
                ];
            }

            return [
                'ok' => false,
                'status' => 402,
                'code' => 'trial_expired',
                'message' => 'Your free trial has ended. Please purchase this book to keep reading.',
                'trial' => $existing,
                'promotion' => $existing->promotion,
                'payment_required' => true,
            ];
        }

        if (! $promo) {
            return [
                'ok' => false,
                'status' => 402,
                'code' => 'payment_required',
                'message' => 'No free trial is available for this book. Please purchase to continue.',
                'payment_required' => true,
            ];
        }

        $days = $promo->resolvedTrialDays();
        $trial = UserAccessTrial::create([
            'user_id' => $user->id,
            'promotion_id' => $promo->id,
            'book_id' => $book->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($days),
        ]);

        return [
            'ok' => true,
            'status' => 201,
            'code' => 'trial_started',
            'message' => "Free trial started. You can read for {$days} days.",
            'trial' => $trial,
            'promotion' => $promo,
            'payment_required' => false,
        ];
    }
}
