<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BooksController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ImageController;


Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/create-account', [AuthController::class, 'createAccount']);
});

Route::prefix('v1')->group(function () {
    // Public: categories + books (read only)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::get('/books', [BooksController::class, 'index']);
    Route::get('/books/{books}', [BooksController::class, 'show']);



    // Authenticated APIs
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::get('/user/profile', [UserController::class, 'profile']);

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

        Route::resource('books', BooksController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->middleware(RoleMiddleware::class . ':admin,author,user');
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->middleware(RoleMiddleware::class . ':admin,author,user');

        Route::resource('images', ImageController::class)->only(['index', 'store', 'show', 'update', 'destroy']);


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
