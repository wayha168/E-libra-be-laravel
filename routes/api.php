<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [UserController::class, 'me']);

    Route::get('/admin-only', function (Request $request) {
        return response()->json([
            'message' => 'Admin endpoint',
            'data' => null,
        ]);
    })->middleware(RoleMiddleware::class . ':admin');

    Route::get('/author-only', function (Request $request) {
        return response()->json([
            'message' => 'Author endpoint',
            'data' => null,
        ]);
    })->middleware(RoleMiddleware::class . ':author');

    Route::get('/user-only', function (Request $request) {
        return response()->json([
            'message' => 'User endpoint',
            'data' => null,
        ]);
    })->middleware(RoleMiddleware::class . ':user');
});
