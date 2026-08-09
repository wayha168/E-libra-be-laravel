<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\Promotion;
use App\Models\User;

class PromotionNotificationHandler
{
    public static function handleCreated(Promotion $promotion): void
    {
        $promotion->loadMissing(['book:id,title', 'author.user:id,name']);

        $target = $promotion->targetLabel();
        $isTrial = $promotion->isFreeTrial();

        $title = $isTrial ? 'New free trial promotion' : 'New book promotion';
        $offer = $isTrial
            ? ($promotion->resolvedTrialDays() . ' days free trial')
            : ($promotion->discount_percent . '% off');

        $body = "{$offer} on {$target}.";

        $meta = [
            'promotion_id' => $promotion->id,
            'book_id' => $promotion->book_id,
            'author_id' => $promotion->author_id,
            'type' => $promotion->type,
            'discount_percent' => $promotion->discount_percent,
            'trial_days' => $promotion->trial_days,
        ];

        User::query()
            ->whereHas('role', fn ($q) => $q->where('role', 'user'))
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($promotion, $title, $body, $meta) {
                foreach ($users as $user) {
                    if (self::alreadyNotified($user->id, $promotion->id)) {
                        continue;
                    }

                    NotificationService::send($user, 'promotion.new', $title, $body, $meta);
                }
            });
    }

    private static function alreadyNotified(string $userId, string $promotionId): bool
    {
        return AppNotification::query()
            ->where('user_id', $userId)
            ->where('type', 'promotion.new')
            ->where('data->promotion_id', $promotionId)
            ->exists();
    }
}
