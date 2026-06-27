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
use App\Http\Controllers\Api\DashboardOverviewController;
use App\Http\Controllers\Api\AuthorEarningsController;
use App\Http\Controllers\Api\BookFeedbackController;
use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Broadcast;


Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [AuthController::class, 'google']);
    Route::get('/auth/google/config', [AuthController::class, 'googleConfig']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/create-account', [AuthController::class, 'createAccount']);

    // Stripe webhook (no auth)
    Route::post('/stripe/webhook', StripeWebhookController::class);
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

    Route::get('/recommendations', [\App\Http\Controllers\Api\RecommendationController::class, 'index']);
    Route::get('/recommendations/popular', [\App\Http\Controllers\Api\RecommendationController::class, 'popular']);

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
        });

        Route::middleware(RoleMiddleware::class . ':admin,author,super_admin')->group(function () {
            Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
            Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
            Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
            Route::get('/activities', [\App\Http\Controllers\Api\ActivityController::class, 'index']);
        });

        // Admin purchase records
        Route::get('/purchases', [BookPurchaseController::class, 'index'])
            ->middleware(RoleMiddleware::class . ':admin');
        Route::get('/purchases/{purchase}', [BookPurchaseController::class, 'show'])
            ->middleware(RoleMiddleware::class . ':admin');

        // Authenticated: permissions + CRUD for admin/author/user
        Route::get('/permissions', function (Request $request) {
            $user = $request->user();

            // super_admin gets all permissions
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                $permissions = \App\Models\Permission::with('roles')->latest()->paginate(10);

                return response()->json([
                    'message' => 'Permissions fetched successfully',
                    'data' => $permissions,
                ]);
            }

            // otherwise: permissions for user's role
            $permissions = \App\Models\Permission::whereHas('roles', function ($q) use ($user) {
                if (method_exists($user, 'role') && $user->role) {
                    $q->where('roles.id', $user->role->id);
                }
            })->latest()->paginate(10);

            return response()->json([
                'message' => 'Permissions fetched successfully',
                'data' => $permissions,
            ]);
        });

        Route::post('/books/{book}/buy', [BooksController::class, 'buy']);
        Route::post('/books/{book}/like', [BookFeedbackController::class, 'toggleLike']);
        Route::post('/books/{book}/comments', [BookFeedbackController::class, 'storeComment']);
        Route::get('/books/{book}/download', [BooksController::class, 'download']);
        Route::resource('books', BooksController::class)->only(['store', 'update', 'destroy'])->middleware(RoleMiddleware::class . ':admin,author,super_admin');
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->middleware(RoleMiddleware::class . ':admin,author,user');

        Route::resource('images', ImageController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::resource('promotions', \App\Http\Controllers\Api\PromotionController::class)
            ->only(['index', 'store', 'update', 'destroy'])
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
