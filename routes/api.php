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

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::resource('books', BooksController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
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
