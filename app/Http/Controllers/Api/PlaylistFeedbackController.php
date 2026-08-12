<?php

namespace App\Http\Controllers\Api;

use App\Models\Playlist;
use App\Models\PlaylistComment;
use App\Models\PlaylistLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistFeedbackController extends Controller
{
    public function comments(Playlist $playlist): JsonResponse
    {
        if (!$this->ensureVisible($playlist)) {
            return $this->notFound();
        }

        $comments = PlaylistComment::query()
            ->with('user:id,name,email')
            ->where('playlist_id', $playlist->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Comments fetched successfully',
            'data' => $comments,
            'meta' => $this->feedbackMeta($playlist),
        ]);
    }

    public function likes(Playlist $playlist): JsonResponse
    {
        if (!$this->ensureVisible($playlist)) {
            return $this->notFound();
        }

        $likes = PlaylistLike::query()
            ->with('user:id,name,email')
            ->where('playlist_id', $playlist->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Likes fetched successfully',
            'data' => $likes,
            'meta' => $this->feedbackMeta($playlist),
        ]);
    }

    public function feedback(Playlist $playlist): JsonResponse
    {
        if (!$this->ensureVisible($playlist)) {
            return $this->notFound();
        }

        $likes = PlaylistLike::query()
            ->with('user:id,name,email')
            ->where('playlist_id', $playlist->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (PlaylistLike $like) => [
                'id' => $like->id,
                'user' => $like->user ? [
                    'id' => $like->user->id,
                    'name' => $like->user->name,
                    'email' => $like->user->email,
                ] : null,
                'created_at' => $like->created_at?->toIso8601String(),
            ]);

        $comments = PlaylistComment::query()
            ->with('user:id,name,email')
            ->where('playlist_id', $playlist->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (PlaylistComment $comment) => [
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
            'message' => 'Playlist feedback fetched successfully',
            'data' => [
                'likes' => $likes,
                'comments' => $comments,
                'views_count' => (int) $playlist->views_count,
            ],
            'meta' => $this->feedbackMeta($playlist),
        ]);
    }

    public function storeComment(Request $request, Playlist $playlist): JsonResponse
    {
        if (!$this->ensureVisible($playlist)) {
            return $this->notFound();
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = PlaylistComment::create([
            'user_id' => $request->user()->id,
            'playlist_id' => $playlist->id,
            'body' => $data['body'],
        ]);

        $comment->load('user:id,name,email');

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment,
            'meta' => $this->feedbackMeta($playlist),
        ], 201);
    }

    public function toggleLike(Request $request, Playlist $playlist): JsonResponse
    {
        if (!$this->ensureVisible($playlist)) {
            return $this->notFound();
        }

        $user = $request->user();

        $existing = PlaylistLike::query()
            ->where('user_id', $user->id)
            ->where('playlist_id', $playlist->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            PlaylistLike::create([
                'user_id' => $user->id,
                'playlist_id' => $playlist->id,
            ]);
            $liked = true;
        }

        return response()->json([
            'message' => $liked ? 'Playlist liked' : 'Like removed',
            'data' => ['liked' => $liked],
            'meta' => $this->feedbackMeta($playlist),
        ]);
    }

    private function ensureVisible(Playlist $playlist): bool
    {
        return $playlist->canBeViewedBy(auth('sanctum')->user());
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Playlist not found or private',
            'data' => null,
        ], 404);
    }

    private function feedbackMeta(Playlist $playlist): array
    {
        $user = auth('sanctum')->user();

        return [
            'likes_count' => PlaylistLike::where('playlist_id', $playlist->id)->count(),
            'comments_count' => PlaylistComment::where('playlist_id', $playlist->id)->count(),
            'views_count' => (int) $playlist->views_count,
            'user_has_liked' => $user
                ? PlaylistLike::where('playlist_id', $playlist->id)->where('user_id', $user->id)->exists()
                : false,
        ];
    }
}
