<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponseView;
use App\Http\Responses\ApiResponses;
use App\Models\Role;
use App\Models\User;
use App\Support\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user || !$user->password || !Hash::check($validated['password'], $user->password)) {
            return ApiResponses::unauthorized(ApiResponseView::INVALID_CREDENTIALS, null);
        }

        if (isset($user->status) && $user->status !== 'active') {
            return ApiResponses::unauthorized(ApiResponseView::INVALID_CREDENTIALS, null);
        }

        if ($user->isUser()) {
            return response()->json([
                'message' => 'This account cannot access the dashboard. Contact an administrator.',
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return ApiResponses::ok(ApiResponseView::LOGIN_SUCCESSFUL, [
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function googleConfig(): JsonResponse
    {
        return response()->json([
            'message' => 'Google auth config fetched successfully',
            'data' => [
                'client_id' => config('services.google.client_id'),
            ],
        ]);
    }

    public function google(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'redirectUri' => ['nullable', 'string'],
            'redirect_uri' => ['nullable', 'string'],
            'credential' => ['nullable', 'string'],
            'id_token' => ['nullable', 'string'],
        ]);

        if (!$request->filled('code') && !$request->filled('credential') && !$request->filled('id_token')) {
            return response()->json([
                'message' => 'Provide code, credential, or id_token.',
            ], 422);
        }

        try {
            $profile = GoogleAuthService::resolveProfile($request->all());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        $user = User::query()
            ->where('google_id', $profile['google_id'])
            ->orWhere('email', $profile['email'])
            ->first();

        if (!$user) {
            $userRoleId = Role::where('role', 'user')->value('id');

            $user = User::create([
                'name' => $profile['name'],
                'email' => $profile['email'],
                'google_id' => $profile['google_id'],
                'role_id' => $userRoleId,
                'status' => 'active',
                'password' => Hash::make(Str::random(40)),
                'confirm_password' => null,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'google_id' => $profile['google_id'],
                'name' => $user->name ?: $profile['name'],
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if (isset($user->status) && $user->status !== 'active') {
            return ApiResponses::unauthorized(ApiResponseView::INVALID_CREDENTIALS, null);
        }

        if ($user->isUser()) {
            return response()->json([
                'message' => 'This account cannot access the dashboard. Contact an administrator.',
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return ApiResponses::ok(ApiResponseView::LOGIN_SUCCESSFUL, [
            'user' => $user->load('role'),
            'token' => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        return ApiResponses::created(
            ApiResponseView::REGISTER_SUCCESSFUL,
            ['message' => 'Register endpoint not implemented yet']
        );
    }

    public function createAccount(Request $request): JsonResponse
    {
        return ApiResponses::created(
            ApiResponseView::CREATE_ACCOUNT_SUCCESSFUL,
            ['message' => 'Create account endpoint not implemented yet']
        );
    }
}
