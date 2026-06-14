<?php

namespace App\Http\Controllers\View;

use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserBuyBook;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserActivityController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = UserActivity::query()->with(['user:id,name,email', 'actor:id,name,email']);

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            // all activity
        } elseif ($user->isAuthor()) {
            $bookIds = $user->authorProfile?->books()->pluck('id') ?? collect();
            $query->where(function ($q) use ($user, $bookIds) {
                $q->where('user_id', $user->id)
                    ->orWhere('actor_id', $user->id);

                if ($bookIds->isNotEmpty()) {
                    $purchaseIds = UserBuyBook::whereIn('book_id', $bookIds)->pluck('id');
                    $q->orWhere(function ($sub) use ($purchaseIds, $bookIds) {
                        $sub->where('type', 'like', 'purchase.%');
                        if ($purchaseIds->isNotEmpty()) {
                            $sub->where(function ($meta) use ($purchaseIds) {
                                foreach ($purchaseIds->take(50) as $pid) {
                                    $meta->orWhereJsonContains('metadata->purchase_id', $pid);
                                }
                            });
                        }
                    })->orWhere(function ($sub) use ($bookIds) {
                        $sub->where('type', 'like', 'book.%')
                            ->where(function ($meta) use ($bookIds) {
                                foreach ($bookIds->take(50) as $bookId) {
                                    $meta->orWhereJsonContains('metadata->book_id', $bookId);
                                }
                            });
                    });
                }
            });
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('actor_id', $user->id);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('search')) {
            $like = '%' . $request->string('search')->toString() . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $like))
                    ->orWhereHas('actor', fn ($aq) => $aq->where('name', 'like', $like));
            });
        }

        $activities = $query->latest()->paginate(15)->withQueryString();

        return view('dashboard.account.activity.index', compact('activities'));
    }
}
