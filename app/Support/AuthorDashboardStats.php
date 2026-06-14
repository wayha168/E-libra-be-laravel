<?php

namespace App\Support;

use App\Models\BookComment;
use App\Models\Books;
use App\Models\Category;
use App\Models\User;
use App\Models\UserBuyBook;

class AuthorDashboardStats
{
    public static function collect(User $user): array
    {
        $user->loadMissing('authorProfile.books');
        $bookIds = $user->authorProfile?->books()->pluck('id') ?? collect();
        $earnings = AuthorEarnings::forUser($user);

        $paidSales = UserBuyBook::query()
            ->whereIn('book_id', $bookIds)
            ->where('status', 'paid');

        return [
            'scope' => 'author',
            'books' => $bookIds->count(),
            'categories' => Category::count(),
            'sales_count' => (clone $paidSales)->count(),
            'gross_revenue' => round((float) (clone $paidSales)->sum('amount'), 2),
            'net_earnings' => $earnings['net_earnings'],
            'platform_fee' => $earnings['platform_fee_total'],
            'platform_fee_rate' => $earnings['platform_fee_rate'],
            'comments' => $bookIds->isEmpty()
                ? 0
                : BookComment::whereIn('book_id', $bookIds)->count(),
            'has_author_profile' => $earnings['has_author_profile'],
        ];
    }

    public static function recentSales(User $user, int $limit = 8): array
    {
        $user->loadMissing('authorProfile.books');
        $bookIds = $user->authorProfile?->books()->pluck('id') ?? collect();

        if ($bookIds->isEmpty()) {
            return [];
        }

        return UserBuyBook::query()
            ->with(['user:id,name,email', 'book:id,title,price'])
            ->whereIn('book_id', $bookIds)
            ->where('status', 'paid')
            ->latest('purchased_at')
            ->take($limit)
            ->get()
            ->map(function ($sale) {
                $amount = (float) ($sale->amount ?? 0);
                $fee = (float) ($sale->admin_commission_amount ?? 0);

                return [
                    'id' => $sale->id,
                    'book' => $sale->book,
                    'user' => $sale->user,
                    'amount' => $amount,
                    'author_earnings' => max(0, round($amount - $fee, 2)),
                    'status' => $sale->status,
                    'purchased_at' => $sale->purchased_at,
                ];
            })
            ->all();
    }

    public static function recentComments(User $user, int $limit = 5): array
    {
        $user->loadMissing('authorProfile.books');
        $bookIds = $user->authorProfile?->books()->pluck('id') ?? collect();

        if ($bookIds->isEmpty()) {
            return [];
        }

        return BookComment::query()
            ->with(['user:id,name', 'book:id,title'])
            ->whereIn('book_id', $bookIds)
            ->latest()
            ->take($limit)
            ->get()
            ->all();
    }
}
