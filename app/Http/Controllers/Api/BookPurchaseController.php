<?php

namespace App\Http\Controllers\Api;

use App\Models\UserBuyBook;
use App\Support\PurchaseCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookPurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserBuyBook::query()
            ->with([
                'user:id,name,email',
                'book:id,title,price,category_id,author_id',
                'book.author.user:id,name,email',
            ])
            ->latest('purchased_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->string('book_id')->toString());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->string('user_id')->toString());
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('book', fn ($bq) => $bq->where('title', 'like', "%{$search}%"));
            });
        }

        $purchases = $query->paginate(15);
        $rate = PurchaseCommission::rate();

        $purchases->getCollection()->transform(function (UserBuyBook $sale) use ($rate) {
            return $this->present($sale, $rate);
        });

        $paidQuery = UserBuyBook::query()->where('status', 'paid');
        $summary = [
            'gross_revenue' => round((float) (clone $paidQuery)->sum('amount'), 2),
            'company_commission_total' => round((float) (clone $paidQuery)->sum('admin_commission_amount'), 2),
            'author_net_total' => round(
                (float) (clone $paidQuery)->sum('amount') - (float) (clone $paidQuery)->sum('admin_commission_amount'),
                2
            ),
            'platform_fee_rate' => $rate,
            'paid_sales_count' => (clone $paidQuery)->count(),
        ];

        return response()->json([
            'message' => 'Purchase records fetched successfully',
            'data' => $purchases,
            'summary' => $summary,
        ]);
    }

    public function show(UserBuyBook $purchase): JsonResponse
    {
        $purchase->load([
            'user:id,name,email',
            'book:id,title,price,category_id,description,author_id',
            'book.author.user:id,name,email',
        ]);

        return response()->json([
            'message' => 'Purchase record fetched successfully',
            'data' => $this->present($purchase, PurchaseCommission::rate()),
        ]);
    }

    private function present(UserBuyBook $sale, float $rate): array
    {
        $amount = (float) ($sale->amount ?? 0);
        $fee = (float) ($sale->admin_commission_amount ?? round($amount * ($rate / 100), 2));

        return [
            'id' => $sale->id,
            'user_id' => $sale->user_id,
            'book_id' => $sale->book_id,
            'status' => $sale->status,
            'amount' => $amount,
            'payment_method' => $sale->payment_method,
            'payment_method_label' => $sale->paymentMethodLabel(),
            'admin_commission_rate' => (float) ($sale->admin_commission_rate ?? $rate),
            'admin_commission_amount' => $fee,
            'company_cut' => $fee,
            'author_earnings' => max(0, round($amount - $fee, 2)),
            'payway_tran_id' => $sale->payway_tran_id,
            'stripe_checkout_session_id' => $sale->stripe_checkout_session_id,
            'purchased_at' => $sale->purchased_at?->toIso8601String(),
            'buyer' => $sale->user ? [
                'id' => $sale->user->id,
                'name' => $sale->user->name,
                'email' => $sale->user->email,
            ] : null,
            'book' => $sale->book ? [
                'id' => $sale->book->id,
                'title' => $sale->book->title,
                'price' => $sale->book->price,
                'author_name' => $sale->book->author?->user?->name,
            ] : null,
        ];
    }
}
