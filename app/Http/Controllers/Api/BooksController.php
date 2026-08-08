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
use App\Support\AuthorScope;
use App\Support\BookAccess;
use App\Support\BookPdfStorage;
use App\Support\BookPricing;
use App\Support\BookRecommendationService;
use App\Support\BookTrialAccess;
use App\Support\PurchaseCommission;
use App\Support\StoresUploadedFiles;
use App\Support\StoresUploadedImages;
use App\Services\AbaPayWayService;
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
        $data = $this->bookPayloadFromRequest($request);
        $user = $request->user();

        if (AuthorScope::isAuthorOnly($user)) {
            $data['author_id'] = AuthorScope::authorIdOrAbort($user);
        }

        $imageIds = $this->collectUploadedImageIds($request, $data['title'] ?? null);
        if ($imageIds !== []) {
            $data['image_id'] = $imageIds[0];
        }

        if ($request->hasFile('pdf_file')) {
            $pdf = StoresUploadedFiles::storePdf($request->file('pdf_file'));
            if ($pdf) {
                $data['pdf_file'] = $pdf['pdf_file'];
                $data['pdf_preview_path'] = $pdf['pdf_preview_path'];
            }
        }

        $book = Books::create($data);
        StoresUploadedImages::attachToBook($book, $imageIds);
        $book->load(['category', 'author.user', 'image']);
        BookAccess::appendAccessMeta($book, $user);

        BookRecommendationService::notifyInterestedUsers($book);

        return response()->json([
            'message' => 'Book created successfully',
            'data' => $book,
        ], 201);
    }

    public function show(Books $book)
    {
        $user = auth('sanctum')->user();
        $book->load(['category', 'author.user.abaPaywayMerchant', 'image']);
        BookAccess::appendAccessMeta($book, $user);
        $this->appendFeedbackMeta($book, $user);
        $this->appendAuthorSocial($book);

        return response()->json([
            'message' => 'Book fetched successfully',
            'data' => $book,
        ]);
    }

    public function update(UpdateBooksRequest $request, Books $book)
    {
        AuthorScope::ensureOwnsBook($request->user(), $book);

        $data = $this->bookPayloadFromRequest($request);

        if (AuthorScope::isAuthorOnly($request->user())) {
            $data['author_id'] = AuthorScope::authorIdOrAbort($request->user());
        }

        $imageIds = $this->collectUploadedImageIds($request, $data['title'] ?? $book->title);
        if ($imageIds !== [] && ! $book->image_id) {
            $data['image_id'] = $imageIds[0];
        }

        if ($request->hasFile('pdf_file')) {
            $pdf = StoresUploadedFiles::storePdf($request->file('pdf_file'));
            if ($pdf) {
                $data['pdf_file'] = $pdf['pdf_file'];
                $data['pdf_preview_path'] = $pdf['pdf_preview_path'];
            }
        }

        $book->update($data);
        StoresUploadedImages::attachToBook($book->fresh(), $imageIds);
        $book = $book->fresh(['category', 'author.user', 'image']);
        BookAccess::appendAccessMeta($book, $request->user());

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

    public function buy(Request $request, Books $book, StripePaymentService $stripe, AbaPayWayService $payway)
    {
        $request->validate([
            'payment_method' => 'nullable|in:card,khqr,stripe_khqr,payway_khqr',
        ]);

        $paymentMethod = $this->normalizePaymentMethod($request->input('payment_method', 'card'));
        $user = $request->user();
        $book->loadMissing('author.user');

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

        if ($paymentMethod === 'payway_khqr') {
            return $this->buyWithPayway($user, $book, $existing, $payway);
        }

        $stripeMethod = $paymentMethod === 'stripe_khqr' ? 'khqr' : 'card';

        if ($existing && $existing->status === 'pending' && $existing->stripe_checkout_session_id) {
            $checkoutUrl = null;

            if ($existing->payment_method !== $paymentMethod) {
                $existing->update(['payment_method' => $paymentMethod]);
            }

            if ($stripe->isConfigured()) {
                try {
                    $session = $stripe->createBookCheckoutSession($user, $book, $existing->fresh(), $stripeMethod);
                    $existing->update(['stripe_checkout_session_id' => $session->id]);
                    $checkoutUrl = $session->url;
                } catch (\Throwable) {
                    // keep existing session id
                }
            }

            return response()->json([
                'message' => 'Checkout session already created.',
                'data' => [
                    'provider' => 'stripe',
                    'purchase' => $existing->fresh(),
                    'checkout_session_id' => $existing->stripe_checkout_session_id,
                    'checkout_url' => $checkoutUrl,
                    'stripe_public_key' => config('services.stripe.public'),
                    'payment_method' => $paymentMethod,
                ],
            ]);
        }

        $effectivePrice = BookPricing::effectivePrice($book) ?? (float) $book->price;

        $purchase = UserBuyBook::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            [
                'amount' => $effectivePrice,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'purchased_at' => null,
            ]
        );

        if (! $stripe->isConfigured()) {
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

        $session = $stripe->createBookCheckoutSession($user, $book, $purchase, $stripeMethod);

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
                'provider' => 'stripe',
                'purchase' => $purchase->fresh(),
                'checkout_session_id' => $session->id,
                'checkout_url' => $session->url,
                'stripe_public_key' => config('services.stripe.public'),
                'payment_method' => $paymentMethod,
            ],
        ], 201);
    }

    public function paymentOptions(Books $book, StripePaymentService $stripe, AbaPayWayService $payway)
    {
        $book->loadMissing('author.user');
        $authorUserId = $book->author?->user_id;
        $paywayConfigured = $authorUserId ? $payway->isConfiguredFor($authorUserId) : false;
        $merchant = $authorUserId ? $payway->forUser($authorUserId) : null;

        return response()->json([
            'message' => 'Payment options fetched successfully',
            'data' => [
                'book_id' => $book->id,
                'price' => BookPricing::effectivePrice($book) ?? (float) $book->price,
                'currency' => strtoupper((string) ($merchant?->currency ?: config('services.stripe.currency', 'usd'))),
                'trial_pages' => BookAccess::trialPages(),
                'admin_commission_rate' => PurchaseCommission::rate(),
                'methods' => [
                    'card' => [
                        'available' => $stripe->isConfigured(),
                        'provider' => 'stripe',
                        'label' => 'Card (Stripe)',
                    ],
                    'stripe_khqr' => [
                        'available' => $stripe->isConfigured() && (bool) config('services.stripe.khqr_enabled', true),
                        'provider' => 'stripe',
                        'label' => 'KHQR (Stripe)',
                    ],
                    'payway_khqr' => [
                        'available' => $paywayConfigured,
                        'provider' => 'aba_payway',
                        'label' => 'Personal KHQR (ABA PayWay)',
                        'merchant_name' => $merchant?->merchant_name,
                        'payment_option' => $merchant?->payment_option ?: 'abapay_khqr',
                    ],
                ],
            ],
        ]);
    }

    public function requestAccess(Request $request, Books $book)
    {
        $user = $request->user();
        $book->load(['category', 'author.user']);

        if (BookAccess::canAccessFull($user, $book)) {
            BookAccess::appendAccessMeta($book, $user);

            return response()->json([
                'message' => 'You already have full access.',
                'code' => 'already_entitled',
                'payment_required' => false,
                'data' => $book,
            ]);
        }

        $result = BookTrialAccess::claim($user, $book);
        BookAccess::appendAccessMeta($book->fresh(['category', 'author.user']), $user);

        $payload = [
            'message' => $result['message'],
            'code' => $result['code'],
            'payment_required' => (bool) ($result['payment_required'] ?? false),
            'buy_url' => url('/api/v1/books/' . $book->id . '/buy'),
            'data' => $book,
        ];

        if (! empty($result['trial'])) {
            $payload['trial'] = [
                'starts_at' => $result['trial']->starts_at?->toIso8601String(),
                'ends_at' => $result['trial']->ends_at?->toIso8601String(),
                'days' => $result['promotion']?->resolvedTrialDays(),
            ];
        }

        return response()->json($payload, $result['status']);
    }

    public function download(Request $request, Books $book)
    {
        // Optional auth: guests can read free books; paid books still require purchase/trial
        $user = $request->user('sanctum') ?? auth('sanctum')->user();

        if (! BookAccess::canAccessFull($user, $book) && $user) {
            $claim = BookTrialAccess::claim($user, $book);
            if (! $claim['ok']) {
                return response()->json([
                    'message' => $claim['message'],
                    'code' => $claim['code'],
                    'payment_required' => true,
                    'buy_url' => url('/api/v1/books/' . $book->id . '/buy'),
                    'request_access_url' => url('/api/v1/books/' . $book->id . '/request-access'),
                    'effective_price' => BookPricing::effectivePrice($book),
                ], 402);
            }
        }

        if (! BookAccess::canAccessFull($user, $book)) {
            return response()->json([
                'message' => BookAccess::isPaid($book)
                    ? 'You must purchase this book or start a free trial to access the full PDF.'
                    : 'You do not have access to this book.',
                'code' => 'payment_required',
                'payment_required' => BookAccess::isPaid($book),
                'buy_url' => BookAccess::isPaid($book) ? url('/api/v1/books/' . $book->id . '/buy') : null,
                'preview_url' => BookAccess::canPreview($book) ? url('/api/v1/books/' . $book->id . '/preview') : null,
                'effective_price' => BookPricing::effectivePrice($book),
            ], 402);
        }

        $path = BookPdfStorage::resolveFullPath($book);
        if (! $path) {
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

    private function buyWithPayway($user, Books $book, ?UserBuyBook $existing, AbaPayWayService $payway)
    {
        $authorUserId = $book->author?->user_id;
        if (! $authorUserId || ! $payway->isConfiguredFor($authorUserId)) {
            return response()->json([
                'message' => 'Author ABA PayWay KHQR is not configured for this book.',
                'code' => 'payway_not_configured',
            ], 422);
        }

        $effectivePrice = BookPricing::effectivePrice($book) ?? (float) $book->price;

        $purchase = UserBuyBook::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            [
                'amount' => $effectivePrice,
                'payment_method' => 'payway_khqr',
                'status' => 'pending',
                'purchased_at' => null,
            ]
        );

        $tranId = Str::limit(str_replace('-', '', (string) $purchase->id), 20, '');
        $purchase->update(['payway_tran_id' => $tranId]);

        try {
            $payload = $payway->buildBookPurchasePayload($user, $book->loadMissing('author'), $purchase->fresh(), [
                'payment_option' => $payway->forUser($authorUserId)?->payment_option ?: 'abapay_khqr',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'payway_error',
            ], 422);
        }

        event(new PurchaseStatusUpdated($purchase->fresh()));
        DashboardOverviewController::broadcastStats();

        return response()->json([
            'message' => 'ABA PayWay KHQR checkout ready',
            'data' => [
                'provider' => 'aba_payway',
                'payment_method' => 'payway_khqr',
                'purchase' => $purchase->fresh(),
                'endpoint' => $payload['endpoint'],
                'fields' => $payload['fields'],
                'tran_id' => $tranId,
            ],
        ], 201);
    }

    private function normalizePaymentMethod(?string $method): string
    {
        $method = $method ?: 'card';

        return match ($method) {
            'khqr' => 'stripe_khqr',
            default => $method,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function bookPayloadFromRequest(StoreBooksRequest|UpdateBooksRequest $request): array
    {
        $data = $request->validated();
        unset($data['image_file'], $data['image_files'], $data['pdf_file']);

        return $data;
    }

    /**
     * @return list<string>
     */
    private function collectUploadedImageIds(Request $request, ?string $title): array
    {
        $imageIds = [];

        if ($request->hasFile('image_file')) {
            $imageIds[] = StoresUploadedImages::store(
                $request->file('image_file'),
                'book',
                $title
            );
        }

        if ($request->hasFile('image_files')) {
            $imageIds = array_merge(
                $imageIds,
                StoresUploadedImages::storeMany($request->file('image_files'), 'book', $title)
            );
        }

        return array_values(array_filter($imageIds));
    }

    private function appendAuthorSocial(Books $book): void
    {
        if (! $book->relationLoaded('author') || ! $book->author) {
            return;
        }

        $book->author->makeVisible([
            'website', 'facebook', 'instagram', 'twitter', 'tiktok', 'youtube', 'telegram', 'bio',
        ]);
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
