<?php

namespace App\Http\Controllers\Api;

use App\Models\UserBuyBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookPurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserBuyBook::query()
            ->with(['user:id,name,email', 'book:id,title,price,category_id'])
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

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('book', fn ($bq) => $bq->where('title', 'like', "%{$search}%"));
            });
        }

        $purchases = $query->paginate(15);

        return response()->json([
            'message' => 'Purchase records fetched successfully',
            'data' => $purchases,
        ]);
    }

    public function show(UserBuyBook $purchase): JsonResponse
    {
        $purchase->load(['user:id,name,email', 'book:id,title,price,category_id,description']);

        return response()->json([
            'message' => 'Purchase record fetched successfully',
            'data' => $purchase,
        ]);
    }
}
