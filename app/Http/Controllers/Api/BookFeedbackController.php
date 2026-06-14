<?php

namespace App\Http\Controllers\Api;

use App\Events\BookFeedbackUpdated;
use App\Http\Controllers\Api\DashboardOverviewController;
use App\Models\BookComment;
use App\Models\BookLike;
use App\Models\Books;
use App\Support\BookFeedbackNotificationHandler;
use App\Support\BookRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookFeedbackController extends Controller
{
    public function comments(Books $book): JsonResponse
    {
        $comments = BookComment::query()
            ->with('user:id,name,email')
            ->where('book_id', $book->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Comments fetched successfully',
            'data' => $comments,
            'meta' => $this->feedbackMeta($book),
        ]);
    }

    public function likes(Books $book): JsonResponse
    {
        $likes = BookLike::query()
            ->with('user:id,name,email')
            ->where('book_id', $book->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Likes fetched successfully',
            'data' => $likes,
            'meta' => $this->feedbackMeta($book),
        ]);
    }

    public function feedback(Books $book): JsonResponse
    {
        $likes = BookLike::query()
            ->with('user:id,name,email')
            ->where('book_id', $book->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (BookLike $like) => [
                'id' => $like->id,
                'user' => $like->user ? [
                    'id' => $like->user->id,
                    'name' => $like->user->name,
                    'email' => $like->user->email,
                ] : null,
                'created_at' => $like->created_at?->toIso8601String(),
            ]);

        $comments = BookComment::query()
            ->with('user:id,name,email')
            ->where('book_id', $book->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (BookComment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email,
                ] : null,
                'created_at' => $comment->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'message' => 'Book feedback fetched successfully',
            'data' => [
                'likes' => $likes,
                'comments' => $comments,
            ],
            'meta' => $this->feedbackMeta($book),
        ]);
    }

    public function storeComment(Request $request, Books $book): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = BookComment::create([
            'user_id' => $request->user()->id,
            'book_id' => $book->id,
            'body' => $data['body'],
        ]);

        $comment->load('user:id,name,email');

        event(new BookFeedbackUpdated($book->id, 'comment.created', [
            'comment' => $comment,
            'meta' => $this->feedbackMeta($book),
        ]));

        BookRecommendationService::notifyFromInteraction($request->user(), $book, 'comment');

        BookFeedbackNotificationHandler::handleComment($request->user(), $book, $comment);

        DashboardOverviewController::broadcastStats();

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment,
            'meta' => $this->feedbackMeta($book),
        ], 201);
    }

    public function toggleLike(Request $request, Books $book): JsonResponse
    {
        $user = $request->user();

        $existing = BookLike::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            BookLike::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
            $liked = true;
        }

        $meta = $this->feedbackMeta($book);

        event(new BookFeedbackUpdated($book->id, 'like.updated', [
            'liked' => $liked,
            'user_id' => $user->id,
            'meta' => $meta,
        ]));

        if ($liked) {
            BookRecommendationService::notifyFromInteraction($user, $book, 'like');
            BookFeedbackNotificationHandler::handleLike($user, $book);
        }

        return response()->json([
            'message' => $liked ? 'Book liked' : 'Like removed',
            'data' => ['liked' => $liked],
            'meta' => $meta,
        ]);
    }

    private function feedbackMeta(Books $book): array
    {
        $user = auth('sanctum')->user();

        return [
            'likes_count' => BookLike::where('book_id', $book->id)->count(),
            'comments_count' => BookComment::where('book_id', $book->id)->count(),
            'user_has_liked' => $user
                ? BookLike::where('book_id', $book->id)->where('user_id', $user->id)->exists()
                : false,
        ];
    }
}
