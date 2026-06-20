<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreBooksRequest;
use App\Http\Requests\UpdateBooksRequest;
use App\Models\BookComment;
use App\Models\BookLike;
use App\Models\Books;
use App\Models\UserBuyBook;
use App\Events\PurchaseStatusUpdated;
use App\Http\Controllers\Api\DashboardOverviewController;
use App\Support\BookAccess;
use App\Support\BookPdfStorage;
use App\Support\BookRecommendationService;
use App\Support\PurchaseCommission;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BooksController extends Controller
{
    public function index(Request $request)
    {
        $query = Books::query()->with('category');

        // Filters
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($qc) use ($search) {
                        $qc->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->string('category_id')->toString());
        }

        if ($request->filled('public_date')) {
            $query->whereDate('public_date', $request->string('public_date')->toString());
        }

        $user = auth('sanctum')->user();
        $books = $query->latest()->paginate(10);

        $books->getCollection()->transform(function ($book) use ($user) {
            BookAccess::appendAccessMeta($book, $user);
            $this->appendFeedbackMeta($book, $user);

            return $book;
        });

        return response()->json([
            'message' => 'Books fetched successfully',
            'data' => $books,
        ]);
    }

    public function store(StoreBooksRequest $request)
    {
        $book = Books::create($request->validated());
        $book->load('category');

        BookRecommendationService::notifyInterestedUsers($book);

        return response()->json([
            'message' => 'Book created successfully',
            'data' => $book,
        ], 201);
    }

    public function show(Books $book)
    {
        $user = auth('sanctum')->user();
        BookAccess::appendAccessMeta($book, $user);
        $this->appendFeedbackMeta($book, $user);

        return response()->json([
            'message' => 'Book fetched successfully',
            'data' => $book,
        ]);
    }

    public function update(UpdateBooksRequest $request, Books $book)
    {
        $book->update($request->validated());

        return response()->json([
            'message' => 'Book updated successfully',
            'data' => $book,
        ]);
    }

    public function destroy(Books $book)
    {
        $book->delete();

        return response()->json([
            'message' => 'Book deleted successfully',
            'data' => null,
        ]);
    }

    public function buy(Request $request, Books $book, StripePaymentService $stripe)
    {
        $request->validate([
            'payment_method' => 'nullable|in:card,khqr',
        ]);

        $paymentMethod = $request->input('payment_method', 'card');
        $user = $request->user();

        if (is_null($book->price) || $book->price <= 0) {
            return response()->json([
                'message' => 'This book is free, no purchase required.',
            ], 400);
        }

        $existing = UserBuyBook::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($existing && $existing->status === 'paid') {
            return response()->json([
                'message' => 'You have already purchased this book.',
                'data' => $existing,
            ]);
        }

        if ($existing && $existing->status === 'pending' && $existing->stripe_checkout_session_id) {
            $checkoutUrl = null;

            if ($existing->payment_method !== $paymentMethod) {
                $existing->update(['payment_method' => $paymentMethod]);
            }

            if ($stripe->isConfigured()) {
                try {
                    $session = $stripe->createBookCheckoutSession($user, $book, $existing->fresh(), $paymentMethod);
                    $existing->update(['stripe_checkout_session_id' => $session->id]);
                    $checkoutUrl = $session->url;
                } catch (\Throwable) {
                    // keep existing session id
                }
            }

            return response()->json([
                'message' => 'Checkout session already created.',
                'data' => [
                    'purchase' => $existing->fresh(),
                    'checkout_session_id' => $existing->stripe_checkout_session_id,
                    'checkout_url' => $checkoutUrl,
                    'stripe_public_key' => config('services.stripe.public'),
                    'payment_method' => $paymentMethod,
                ],
            ]);
        }

        $effectivePrice = \App\Support\BookPricing::effectivePrice($book) ?? (float) $book->price;

        $purchase = UserBuyBook::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            [
                'amount' => $effectivePrice,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'purchased_at' => null,
            ]
        );

        if (!$stripe->isConfigured()) {
            $purchase->update([
                'status' => 'paid',
                'purchased_at' => now(),
                'payment_method' => $paymentMethod,
            ]);

            $purchase = PurchaseCommission::applyToPurchase($purchase->fresh());
            event(new PurchaseStatusUpdated($purchase));
            DashboardOverviewController::broadcastStats();

            return response()->json([
                'message' => 'Book purchased successfully',
                'data' => $purchase,
            ], 201);
        }

        $session = $stripe->createBookCheckoutSession($user, $book, $purchase, $paymentMethod);

        $purchase->update([
            'stripe_checkout_session_id' => $session->id,
            'payment_method' => $paymentMethod,
        ]);

        $purchase = $purchase->fresh();
        event(new PurchaseStatusUpdated($purchase));
        DashboardOverviewController::broadcastStats();

        return response()->json([
            'message' => 'Stripe checkout session created',
            'data' => [
                'purchase' => $purchase->fresh(),
                'checkout_session_id' => $session->id,
                'checkout_url' => $session->url,
                'stripe_public_key' => config('services.stripe.public'),
                'payment_method' => $paymentMethod,
            ],
        ], 201);
    }

    public function download(Request $request, Books $book)
    {
        $user = $request->user();

        if (!BookAccess::canAccessFull($user, $book)) {
            return response()->json([
                'message' => 'You must purchase this book or subscribe to access the full PDF.',
            ], 403);
        }

        $path = BookPdfStorage::resolveFullPath($book);
        if (!$path) {
            return response()->json([
                'message' => 'This book does not have a PDF file available.',
            ], 404);
        }

        return BookPdfStorage::streamFile($path, Str::slug($book->title) . '.pdf', false);
    }

    public function preview(Books $book)
    {
        $user = auth('sanctum')->user();

        if (BookAccess::canAccessFull($user, $book)) {
            return response()->json([
                'message' => 'You already have full access. Use the download endpoint instead.',
            ], 403);
        }

        if (!BookAccess::isPaid($book) || !BookAccess::hasPdf($book)) {
            return response()->json([
                'message' => BookAccess::hasPdf($book) ? 'Preview is not available for this book.' : 'No PDF available for preview.',
            ], BookAccess::hasPdf($book) ? 403 : 404);
        }

        $path = BookPdfStorage::resolvePreviewPath($book);
        if (!$path) {
            return response()->json(['message' => 'Preview file not found.'], 404);
        }

        return BookPdfStorage::streamFile($path, Str::slug($book->title) . '-preview.pdf', true);
    }

    private function canAccessBook($user, Books $book): bool
    {
        return BookAccess::canAccessFull($user, $book);
    }

    private function appendFeedbackMeta(Books $book, $user): void
    {
        $book->likes_count = BookLike::where('book_id', $book->id)->count();
        $book->comments_count = BookComment::where('book_id', $book->id)->count();
        $book->user_has_liked = $user
            ? BookLike::where('book_id', $book->id)->where('user_id', $user->id)->exists()
            : false;
    }
}
