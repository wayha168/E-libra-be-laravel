<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserBuyBook;
use Illuminate\Support\Collection;

class AuthorEarnings
{
    public static function forUser(User $user): array
    {
        $user->loadMissing('authorProfile.books');

        $author = $user->authorProfile;
        if (!$author) {
            return [
                'has_author_profile' => false,
                'sales_count' => 0,
                'gross_revenue' => 0.0,
                'platform_fee_total' => 0.0,
                'platform_fee_rate' => PurchaseCommission::rate(),
                'net_earnings' => 0.0,
                'sales' => [],
                'payway_account' => $user->payway_account,
                'bakong_account' => $user->bakong_account,
            ];
        }

        $bookIds = $author->books()->pluck('id');

        $sales = $bookIds->isEmpty()
            ? collect()
            : UserBuyBook::query()
                ->with(['user:id,name,email', 'book:id,title,price'])
                ->whereIn('book_id', $bookIds)
                ->where('status', 'paid')
                ->latest('purchased_at')
                ->get();

        return self::summarize($sales, $user);
    }

    public static function forAuthorId(string $authorId): array
    {
        $author = \App\Models\Author::with('user')->find($authorId);
        if (!$author || !$author->user) {
            return self::forUser(new User());
        }

        return self::forUser($author->user);
    }

    private static function summarize(Collection $sales, User $user): array
    {
        $rate = PurchaseCommission::rate();
        $gross = (float) $sales->sum('amount');
        $platformFee = (float) $sales->sum('admin_commission_amount');

        $mappedSales = $sales->map(function ($sale) use ($rate) {
            $amount = (float) ($sale->amount ?? 0);
            $fee = (float) ($sale->admin_commission_amount ?? round($amount * ($rate / 100), 2));

            return [
                'id' => $sale->id,
                'book_id' => $sale->book_id,
                'book_title' => $sale->book?->title,
                'buyer_name' => $sale->user?->name,
                'buyer_email' => $sale->user?->email,
                'amount' => $amount,
                'payment_method' => $sale->payment_method ?? 'card',
                'payment_method_label' => $sale->paymentMethodLabel(),
                'platform_fee_rate' => (float) ($sale->admin_commission_rate ?? $rate),
                'platform_fee' => $fee,
                'author_earnings' => max(0, round($amount - $fee, 2)),
                'purchased_at' => $sale->purchased_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'has_author_profile' => true,
            'author_id' => $user->authorProfile?->id,
            'sales_count' => $sales->count(),
            'gross_revenue' => round($gross, 2),
            'platform_fee_total' => round($platformFee, 2),
            'platform_fee_rate' => $rate,
            'net_earnings' => round(max(0, $gross - $platformFee), 2),
            'sales' => $mappedSales,
            'payway_account' => $user->payway_account,
            'bakong_account' => $user->bakong_account,
        ];
    }
}
