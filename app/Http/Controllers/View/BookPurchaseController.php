<?php

namespace App\Http\Controllers\View;

use App\Models\UserBuyBook;
use App\Support\AuthorScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookPurchaseController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isAuthorView = AuthorScope::isAuthorOnly($user);

        $query = UserBuyBook::query()
            ->with(['user', 'book'])
            ->latest('purchased_at');

        $query = AuthorScope::scopePurchasesToAuthorBooks($query, $user);

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

        $statsQuery = AuthorScope::scopePurchasesToAuthorBooks(UserBuyBook::query(), $user);

        $aggregate = (clone $statsQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as revenue,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN admin_commission_amount ELSE 0 END), 0) as admin_commission,
                COALESCE(SUM(
                    CASE WHEN status = 'paid'
                    THEN GREATEST(0, amount - COALESCE(admin_commission_amount, 0))
                    ELSE 0 END
                ), 0) as author_net
            ")
            ->first();

        $stats = [
            'total' => (int) ($aggregate->total ?? 0),
            'paid' => (int) ($aggregate->paid ?? 0),
            'pending' => (int) ($aggregate->pending ?? 0),
            'revenue' => (float) ($aggregate->revenue ?? 0),
            'admin_commission' => (float) ($aggregate->admin_commission ?? 0),
            'author_net' => (float) ($aggregate->author_net ?? 0),
        ];

        return view('dashboard.purchases.index', compact('purchases', 'stats', 'isAuthorView'));
    }

    public function show(Request $request, UserBuyBook $purchase): View
    {
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isSuperAdmin()) {
            $purchase->loadMissing('book:id,author_id');

            $isBuyer = $purchase->user_id === $user->id;
            $isBookAuthor = $user->authorProfile?->id === $purchase->book?->author_id;

            if (! $isBuyer && ! $isBookAuthor) {
                abort(403);
            }
        }

        $purchase->load(['user', 'book.author.user', 'book.category']);

        return view('dashboard.purchases.show', compact('purchase'));
    }
}
