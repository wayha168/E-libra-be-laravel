<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponseView;
use App\Http\Responses\ApiResponses;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{


    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return ApiResponses::unauthorized(ApiResponseView::INVALID_CREDENTIALS, null);
        }

        // block inactive users
        if (isset($user->status) && $user->status !== 'active') {
            return ApiResponses::unauthorized(ApiResponseView::INVALID_CREDENTIALS, null);
        }


        $token = $user->createToken('api-token')->plainTextToken;

        return ApiResponses::ok(ApiResponseView::LOGIN_SUCCESSFUL, [
            'user' => $user,
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
