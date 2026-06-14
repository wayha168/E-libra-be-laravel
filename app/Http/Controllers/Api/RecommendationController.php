<?php

namespace App\Http\Controllers\Api;

use App\Models\BookComment;
use App\Models\BookLike;
use App\Models\Books;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $limit = min(24, max(1, (int) $request->input('limit', 12)));

        $books = $user
            ? \App\Support\BookRecommendationService::forUser($user, $limit)
            : \App\Support\BookRecommendationService::popularBooks($limit);

        return response()->json([
            'message' => 'Recommendations fetched successfully',
            'data' => $books->map(fn (Books $book) => \App\Support\BookApiPresenter::recommendation($book, $user))->values(),
            'meta' => [
                'personalized' => (bool) $user,
                'count' => $books->count(),
                'limit' => $limit,
            ],
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $limit = min(24, max(1, (int) $request->input('limit', 12)));
        $books = \App\Support\BookRecommendationService::popularBooks($limit);

        return response()->json([
            'message' => 'Popular books fetched successfully',
            'data' => $books->map(fn (Books $book) => \App\Support\BookApiPresenter::recommendation($book, null))->values(),
            'meta' => [
                'personalized' => false,
                'count' => $books->count(),
                'limit' => $limit,
            ],
        ]);
    }

    private function resolveUser(Request $request): ?\App\Models\User
    {
        if ($request->user('sanctum')) {
            return $request->user('sanctum');
        }

        if ($token = $request->bearerToken()) {
            return \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
        }

        return null;
    }
}
