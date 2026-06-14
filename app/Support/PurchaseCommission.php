<?php

namespace App\Support;

use App\Models\UserBuyBook;

class PurchaseCommission
{
    public static function rate(): float
    {
        return (float) config('elibra.admin_commission_rate', 10);
    }

    public static function applyToPurchase(UserBuyBook $purchase): UserBuyBook
    {
        if ($purchase->status !== 'paid' || !$purchase->amount || $purchase->amount <= 0) {
            return $purchase;
        }

        $rate = self::rate();
        $commission = round((float) $purchase->amount * ($rate / 100), 2);

        $purchase->update([
            'admin_commission_rate' => $rate,
            'admin_commission_amount' => $commission,
        ]);

        return $purchase->fresh();
    }
}
