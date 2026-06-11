<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return response()->json([
            'message' => 'User fetched successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->display_role,
                'status' => $user->display_status,
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role.permissions']);

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->display_role,
                'books_count' => $user->authorProfile?->books()->count() ?? 0,
            ],
            'permissions' => $user->role->permissions->map(fn($p) => [
                'name' => $p->name,
                'display_name' => $p->display_name,
            ])->toArray(),
        ]);
    }
}