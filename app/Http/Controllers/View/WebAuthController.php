<?php

namespace App\Http\Controllers\View;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class WebAuthController
{
    public function establishSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $accessToken = PersonalAccessToken::findToken($validated['token']);

        if (!$accessToken || !$accessToken->tokenable instanceof User) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user = $accessToken->tokenable;

        if (isset($user->status) && $user->status !== 'active') {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // Block role "user" from establishing a dashboard session
        if (method_exists($user, 'isUser') && $user->isUser()) {
            return response()->json(['message' => 'Credentials are invalid'], 403);
        }

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return response()->json(['message' => 'Session established']);
    }

    public function issueToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (isset($user->status) && $user->status !== 'active') {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->tokens()->where('name', 'api-token')->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}
