<?php

namespace App\Support;

use App\Models\Books;
use App\Models\BookComment;
use App\Models\Category;
use App\Models\User;
use App\Models\UserBuyBook;

class DashboardStats
{
    public static function collect(): array
    {
        return [
            'users' => User::count(),
            'books' => Books::count(),
            'categories' => Category::count(),
            'purchases_paid' => UserBuyBook::where('status', 'paid')->count(),
            'purchases_pending' => UserBuyBook::where('status', 'pending')->count(),
            'revenue' => (float) UserBuyBook::where('status', 'paid')->sum('amount'),
            'admin_commission' => (float) UserBuyBook::where('status', 'paid')->sum('admin_commission_amount'),
            'comments' => BookComment::count(),
        ];
    }
}
