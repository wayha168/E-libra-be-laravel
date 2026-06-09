<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Middleware\RoleMiddleware;

Route::post('/login', function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'role' => optional($request->user()->role)->role,
        ]);
    });

    Route::get('/admin-only', function (Request $request) {
        return response()->json(['message' => 'Admin endpoint']);
    })->middleware(RoleMiddleware::class . ':admin');

    Route::get('/author-only', function (Request $request) {
        return response()->json(['message' => 'Author endpoint']);
    })->middleware(RoleMiddleware::class . ':author');

    Route::get('/user-only', function (Request $request) {
        return response()->json(['message' => 'User endpoint']);
    })->middleware(RoleMiddleware::class . ':user');
});
