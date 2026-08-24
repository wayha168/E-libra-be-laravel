<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BooksController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\BookPurchaseController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\PaywayCallbackController;
use App\Http\Controllers\Api\DashboardOverviewController;
use App\Http\Controllers\Api\AuthorEarningsController;
use App\Http\Controllers\Api\AuthorsController;
use App\Http\Controllers\Api\AbaPaywayMerchantController;
use App\Http\Controllers\Api\BookFeedbackController;
use App\Http\Controllers\Api\BookSaveController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\PlaylistFeedbackController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Broadcast;


Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [AuthController::class, 'google']);
    Route::get('/auth/google/config', [AuthController::class, 'googleConfig']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/create-account', [AuthController::class, 'createAccount']);

    // Stripe webhook (no auth)
    Route::post('/stripe/webhook', StripeWebhookController::class);
    // ABA PayWay return/callback (no auth — verified via purchase/tran_id)
    Route::match(['get', 'post'], '/payway/callback', PaywayCallbackController::class);
});

Route::prefix('v1')->group(function () {
    // Public: categories + books (read only, no authentication required)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::get('/books', [BooksController::class, 'index']);
    Route::get('/books/{book}', [BooksController::class, 'show']);
    Route::get('/books/{book}/comments', [BookFeedbackController::class, 'comments']);
    Route::get('/books/{book}/likes', [BookFeedbackController::class, 'likes']);
    Route::get('/books/{book}/feedback', [BookFeedbackController::class, 'feedback']);
    Route::get('/books/{book}/preview', [BooksController::class, 'preview']);
    Route::get('/books/{book}/payment-options', [BooksController::class, 'paymentOptions']);
    // Public read: free books (full PDF) + paid books (preview only via /preview)
    Route::get('/books/{book}/download', [BooksController::class, 'download']);

    // Public authors
    Route::get('/authors', [AuthorsController::class, 'index']);
    Route::get('/authors/{author}', [AuthorsController::class, 'show']);

    // Public: images (read only, no authentication required)
    Route::get('/images', [ImageController::class, 'index']);
    Route::get('/images/{image}', [ImageController::class, 'show']);

    Route::get('/recommendations', [\App\Http\Controllers\Api\RecommendationController::class, 'index']);
    Route::get('/recommendations/popular', [\App\Http\Controllers\Api\RecommendationController::class, 'popular']);

    // Public global search: books, authors, top selling
    Route::get('/search', [SearchController::class, 'index']);
    Route::get('/search/top-selling', [SearchController::class, 'topSelling']);

    // Public: promotions (read only — guests can browse like comments/likes)
    Route::get('/promotions', [\App\Http\Controllers\Api\PromotionController::class, 'index']);
    Route::get('/promotions/{promotion}', [\App\Http\Controllers\Api\PromotionController::class, 'show']);

    // Public playlists (public ones + feedback/views)
    Route::get('/playlists', [PlaylistController::class, 'index']);
    Route::get('/playlists/{playlist}', [PlaylistController::class, 'show']);
    Route::get('/playlists/{playlist}/comments', [PlaylistFeedbackController::class, 'comments']);
    Route::get('/playlists/{playlist}/likes', [PlaylistFeedbackController::class, 'likes']);
    Route::get('/playlists/{playlist}/feedback', [PlaylistFeedbackController::class, 'feedback']);

    // Stripe public key for frontend checkout
    Route::get('/stripe/config', function () {
        return response()->json([
            'message' => 'Stripe config fetched successfully',
            'data' => [
                'public_key' => config('services.stripe.public'),
                'currency' => config('services.stripe.currency', 'usd'),
                'subscription_amount' => (float) config('services.stripe.subscription_amount', 9.99),
                'khqr_enabled' => (bool) config('services.stripe.khqr_enabled', true),
                'admin_commission_rate' => \App\Support\PurchaseCommission::rate(),
                'trial_pages' => \App\Support\BookAccess::trialPages(),
                'payway_note' => 'Author personal KHQR is available per-book via GET /books/{book}/payment-options',
            ],
        ]);
    });


    // Authenticated APIs
    Route::middleware('auth:sanctum')->group(function () {
        Broadcast::routes();

        Route::get('/dashboard/overview', [DashboardOverviewController::class, 'index'])
            ->middleware(RoleMiddleware::class . ':admin,author,super_admin');

        Route::get('/me', [UserController::class, 'me']);
        Route::get('/user/profile', [UserController::class, 'profile']);
        Route::post('/user/subscribe', [UserController::class, 'subscribe']);
        Route::get('/user/purchases', [UserController::class, 'purchases']);
        Route::get('/author/earnings', AuthorEarningsController::class);
        Route::post('/presence/heartbeat', [\App\Http\Controllers\Api\PresenceController::class, 'heartbeat']);

        Route::middleware(RoleMiddleware::class . ':admin,super_admin')->group(function () {
            Route::get('/admin/presence', [\App\Http\Controllers\Api\PresenceController::class, 'index']);

            Route::post('/authors', [AuthorsController::class, 'store']);
            Route::put('/authors/{author}', [AuthorsController::class, 'update']);
            Route::patch('/authors/{author}', [AuthorsController::class, 'update']);
            Route::delete('/authors/{author}', [AuthorsController::class, 'destroy']);

            Route::get('/payway/merchants', [AbaPaywayMerchantController::class, 'index']);
            Route::post('/payway/merchants', [AbaPaywayMerchantController::class, 'store']);
            Route::get('/payway/merchants/{payway}', [AbaPaywayMerchantController::class, 'show']);
            Route::put('/payway/merchants/{payway}', [AbaPaywayMerchantController::class, 'update']);
            Route::patch('/payway/merchants/{payway}', [AbaPaywayMerchantController::class, 'update']);
            Route::delete('/payway/merchants/{payway}', [AbaPaywayMerchantController::class, 'destroy']);
        });

        // Author/admin can manage own PayWay credentials in DB
        Route::middleware(RoleMiddleware::class . ':admin,author,super_admin')->group(function () {
            Route::get('/payway/me', [AbaPaywayMerchantController::class, 'mine']);
            Route::put('/payway/me', [AbaPaywayMerchantController::class, 'upsertMine']);
            Route::post('/payway/me', [AbaPaywayMerchantController::class, 'upsertMine']);
        });

        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);

        Route::middleware(RoleMiddleware::class . ':admin,author,super_admin')->group(function () {
            Route::get('/activities', [\App\Http\Controllers\Api\ActivityController::class, 'index']);
        });

        // Admin purchase records (company cut)
        Route::get('/purchases', [BookPurchaseController::class, 'index'])
            ->middleware(RoleMiddleware::class . ':admin,super_admin');
        Route::get('/purchases/{purchase}', [BookPurchaseController::class, 'show'])
            ->middleware(RoleMiddleware::class . ':admin,super_admin');

        // Permissions: list own / all; grant·revoke only via role checkbox sync (no create)
        Route::get('/permissions', [PermissionController::class, 'index']);

        Route::middleware(RoleMiddleware::class . ':admin,super_admin')->group(function () {
            Route::get('/roles', [RoleController::class, 'index']);
            Route::get('/roles/{role}', [RoleController::class, 'show']);
            Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);

            Route::get('/permissions/{permission}', [PermissionController::class, 'show']);
            Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
        });

        // Playlists — any authenticated user can manage their own
        Route::get('/me/playlists', [PlaylistController::class, 'mine']);
        Route::post('/playlists', [PlaylistController::class, 'store']);
        Route::put('/playlists/{playlist}', [PlaylistController::class, 'update']);
        Route::patch('/playlists/{playlist}', [PlaylistController::class, 'update']);
        Route::delete('/playlists/{playlist}', [PlaylistController::class, 'destroy']);
        Route::post('/playlists/{playlist}/books', [PlaylistController::class, 'addBook']);
        Route::delete('/playlists/{playlist}/books/{book}', [PlaylistController::class, 'removeBook']);
        Route::put('/playlists/{playlist}/books/reorder', [PlaylistController::class, 'reorderBooks']);
        Route::post('/playlists/{playlist}/like', [PlaylistFeedbackController::class, 'toggleLike']);
        Route::post('/playlists/{playlist}/comments', [PlaylistFeedbackController::class, 'storeComment']);

        Route::post('/books/{book}/buy', [BooksController::class, 'buy']);
        Route::get('/payway/status', [BooksController::class, 'paywayStatus']);
        Route::post('/books/{book}/request-access', [BooksController::class, 'requestAccess']);
        Route::post('/books/{book}/like', [BookFeedbackController::class, 'toggleLike']);
        Route::post('/books/{book}/comments', [BookFeedbackController::class, 'storeComment']);

        // Book save/bookmark routes
        Route::get('/me/saved-books', [BookSaveController::class, 'index']);
        Route::post('/books/{book}/save', [BookSaveController::class, 'save']);
        Route::post('/books/{book}/unsave', [BookSaveController::class, 'unsave']);
        Route::post('/books/{book}/save/toggle', [BookSaveController::class, 'toggle']);
        Route::get('/books/{book}/saved', [BookSaveController::class, 'isSaved']);
        Route::post('/books/{book}/add-to-playlist', [BookSaveController::class, 'addToPlaylist']);

        // Offline cache routes for reading books offline
        Route::get('/offline-cache', [BookSaveController::class, 'offlineCache']);
        Route::get('/offline-cache/book/{book}', [BookSaveController::class, 'offlineBook']);

        Route::resource('books', BooksController::class)->only(['store', 'update', 'destroy'])->middleware(RoleMiddleware::class . ':admin,author,super_admin');
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->middleware(RoleMiddleware::class . ':admin,author,user');

        Route::resource('images', ImageController::class)->only(['store', 'update', 'destroy']);

        // Manage promotions (create/update/delete) — public list/show are above
        Route::resource('promotions', \App\Http\Controllers\Api\PromotionController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware(RoleMiddleware::class . ':admin,author,super_admin');


        // ─── Chat (user ↔ admin) ─────────────────────────────────────────────
        // User: get/create own conversation, fetch messages, send message
        Route::get('/chat', [ChatController::class, 'userConversation']);
        Route::get('/chat/messages', [ChatController::class, 'userMessages']);
        Route::post('/chat/messages', [ChatController::class, 'userSend']);

        // Admin: manage all conversations
        Route::middleware(RoleMiddleware::class . ':admin,super_admin')->group(function () {
            Route::get('/admin/chats', [ChatController::class, 'adminConversations']);
            Route::get('/admin/chats/{conversation}', [ChatController::class, 'adminMessages']);
            Route::post('/admin/chats/{conversation}/messages', [ChatController::class, 'adminSend']);
            Route::post('/admin/chats/{conversation}/close', [ChatController::class, 'adminClose']);
        });

        Route::get('/admin-only', function (Request $request) {
            \App\Http\Responses\ApiResponses::ok(
                \App\Http\Responses\ApiResponseView::ADMIN_ENDPOINT,
                null
            );
        })->middleware(RoleMiddleware::class . ':admin');

        Route::get('/author-only', function (Request $request) {
            \App\Http\Responses\ApiResponses::ok(
                \App\Http\Responses\ApiResponseView::AUTHOR_ENDPOINT,
                null
            );
        })->middleware(RoleMiddleware::class . ':author');

        Route::get('/user-only', function (Request $request) {
            \App\Http\Responses\ApiResponses::ok(
                \App\Http\Responses\ApiResponseView::USER_ENDPOINT,
                null
            );
        })->middleware(RoleMiddleware::class . ':user');
    });
});
