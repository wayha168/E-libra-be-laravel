<?php

namespace App\Http\Controllers\Api;

use App\Models\UserActivity;
use App\Models\UserBuyBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = UserActivity::query()->with(['user:id,name,email', 'actor:id,name,email']);

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            // all
        } elseif ($user->isAuthor()) {
            $bookIds = $user->authorProfile?->books()->pluck('id') ?? collect();
            $query->where(function ($q) use ($user, $bookIds) {
                $q->where('user_id', $user->id)->orWhere('actor_id', $user->id);
                if ($bookIds->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($bookIds) {
                        $sub->where('type', 'like', 'purchase.%')
                            ->orWhere(function ($bookSub) use ($bookIds) {
                                $bookSub->where('type', 'like', 'book.%');
                                $bookSub->where(function ($meta) use ($bookIds) {
                                    foreach ($bookIds->take(50) as $bookId) {
                                        $meta->orWhereJsonContains('metadata->book_id', $bookId);
                                    }
                                });
                            });
                    });
                }
            });
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('actor_id', $user->id);
            });
        }

        if ($request->filled('search')) {
            $like = '%' . $request->string('search')->toString() . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)->orWhere('description', 'like', $like);
            });
        }

        $activities = $query->latest()->paginate(15);

        return response()->json([
            'message' => 'Activities fetched successfully',
            'data' => $activities,
        ]);
    }
}
