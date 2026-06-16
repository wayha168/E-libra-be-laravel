<?php

namespace App\Support;

use App\Models\UserBuyBook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStats
{
    public static function collect(): array
    {
        return Cache::remember('dashboard.stats', 90, function () {
            $purchaseStats = UserBuyBook::query()
                ->selectRaw("
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as purchases_paid,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as purchases_pending,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as revenue,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN admin_commission_amount ELSE 0 END), 0) as admin_commission
                ")
                ->first();

            $counts = DB::selectOne('
                SELECT
                    (SELECT COUNT(*) FROM users) as users,
                    (SELECT COUNT(*) FROM books) as books,
                    (SELECT COUNT(*) FROM categories) as categories,
                    (SELECT COUNT(*) FROM book_comments) as comments
            ');

            return [
                'users' => (int) ($counts->users ?? 0),
                'books' => (int) ($counts->books ?? 0),
                'categories' => (int) ($counts->categories ?? 0),
                'purchases_paid' => (int) ($purchaseStats->purchases_paid ?? 0),
                'purchases_pending' => (int) ($purchaseStats->purchases_pending ?? 0),
                'revenue' => (float) ($purchaseStats->revenue ?? 0),
                'admin_commission' => (float) ($purchaseStats->admin_commission ?? 0),
                'comments' => (int) ($counts->comments ?? 0),
            ];
        });
    }

    public static function flush(): void
    {
        Cache::forget('dashboard.stats');
    }
}
