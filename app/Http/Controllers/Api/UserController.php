<?php

namespace App\Http\Controllers\Api;

use App\Models\Books;
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
                'user_subscribe' => (bool) $user->user_subscribe,
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
                'user_subscribe' => (bool) $user->user_subscribe,
            ],
            'permissions' => $user->role->permissions->map(fn($p) => [
                'name' => $p->name,
                'display_name' => $p->display_name,
            ])->toArray(),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_subscribe) {
            return response()->json([
                'message' => 'You already have an active subscription.',
                'data' => [
                    'user_subscribe' => true,
                ],
            ]);
        }

        $user->user_subscribe = true;
        $user->save();

        return response()->json([
            'message' => 'Subscribed successfully',
            'data' => [
                'user_subscribe' => true,
            ],
        ]);
    }

    public function purchases(Request $request): JsonResponse
    {
        $user = $request->user();
        $purchasedBooks = Books::whereIn('id', function ($query) use ($user) {
            $query->select('book_id')
                ->from('users_buys_book')
                ->where('user_id', $user->id)
                ->where('status', 'paid');
        })->latest()->get();

        return response()->json([
            'message' => 'Purchased books fetched successfully',
            'data' => $purchasedBooks,
        ]);
    }
}