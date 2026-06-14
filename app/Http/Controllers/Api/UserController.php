<?php

namespace App\Http\Controllers\Api;

use App\Support\AuthorEarnings;
use App\Models\UserBuyBook;
use App\Services\StripePaymentService;
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
        $user = $request->user()->load(['role.permissions', 'authorProfile']);

        $payload = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->display_role,
                'books_count' => $user->authorProfile?->books()->count() ?? 0,
                'user_subscribe' => (bool) $user->user_subscribe,
                'payway_account' => $user->payway_account,
                'bakong_account' => $user->bakong_account,
            ],
            'permissions' => $user->role?->permissions->map(fn ($p) => [
                'name' => $p->name,
                'display_name' => $p->display_name,
            ])->toArray() ?? [],
        ];

        if ($user->authorProfile || $user->isAuthor()) {
            $payload['author_earnings'] = AuthorEarnings::forUser($user);
        }

        return response()->json($payload);
    }

    public function subscribe(Request $request, StripePaymentService $stripe): JsonResponse
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

        if (!$stripe->isConfigured()) {
            $user->user_subscribe = true;
            $user->save();

            return response()->json([
                'message' => 'Subscribed successfully',
                'data' => [
                    'user_subscribe' => true,
                ],
            ]);
        }

        $session = $stripe->createSubscriptionCheckoutSession($user);

        return response()->json([
            'message' => 'Stripe checkout session created for subscription',
            'data' => [
                'checkout_session_id' => $session->id,
                'checkout_url' => $session->url,
                'stripe_public_key' => config('services.stripe.public'),
                'subscription_amount' => (float) config('services.stripe.subscription_amount', 9.99),
            ],
        ], 201);
    }

    public function purchases(Request $request): JsonResponse
    {
        $user = $request->user();

        $records = UserBuyBook::query()
            ->with(['book:id,title,price,category_id,description,image_id'])
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->latest('purchased_at')
            ->get();

        return response()->json([
            'message' => 'Purchased books fetched successfully',
            'data' => $records,
        ]);
    }
}