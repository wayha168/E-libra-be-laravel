<?php

namespace App\Http\Controllers\View;

use App\Models\UserBuyBook;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookPurchaseController
{
    public function index(Request $request): View
    {
        $query = UserBuyBook::query()
            ->with(['user', 'book'])
            ->latest('purchased_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('book', fn ($bq) => $bq->where('title', 'like', "%{$search}%"));
            });
        }

        $purchases = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => UserBuyBook::count(),
            'paid' => UserBuyBook::where('status', 'paid')->count(),
            'pending' => UserBuyBook::where('status', 'pending')->count(),
            'revenue' => UserBuyBook::where('status', 'paid')->sum('amount'),
            'admin_commission' => UserBuyBook::where('status', 'paid')->sum('admin_commission_amount'),
        ];

        return view('dashboard.purchases.index', compact('purchases', 'stats'));
    }
}
