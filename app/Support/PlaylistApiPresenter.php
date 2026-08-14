<?php

namespace App\Support;

use App\Models\Playlist;
use App\Models\PlaylistComment;
use App\Models\PlaylistLike;

class PlaylistApiPresenter
{
    public static function toArray(Playlist $playlist, $user = null, bool $withBooks = false): array
    {
        $playlist->loadMissing(['user:id,name,email']);

        $data = [
            'id' => $playlist->id,
            'user_id' => $playlist->user_id,
            'name' => $playlist->name,
            'description' => $playlist->description,
            'is_public' => (bool) $playlist->is_public,
            'views_count' => (int) $playlist->views_count,
            'books_count' => (int) ($playlist->books_count ?? $playlist->books()->count()),
            'likes_count' => (int) ($playlist->likes_count ?? PlaylistLike::where('playlist_id', $playlist->id)->count()),
            'comments_count' => (int) ($playlist->comments_count ?? PlaylistComment::where('playlist_id', $playlist->id)->count()),
            'user_has_liked' => $user
                ? PlaylistLike::where('playlist_id', $playlist->id)->where('user_id', $user->id)->exists()
                : false,
            'is_owner' => $playlist->isOwnedBy($user),
            'can_edit' => $playlist->canBeEditedBy($user),
            'can_delete' => $playlist->canBeDeletedBy($user),
            'owner' => $playlist->user ? [
                'id' => $playlist->user->id,
                'name' => $playlist->user->name,
                'email' => $playlist->user->email,
            ] : null,
            'created_at' => $playlist->created_at?->toIso8601String(),
            'updated_at' => $playlist->updated_at?->toIso8601String(),
            'show_url' => url('/api/v1/playlists/' . $playlist->id),
        ];

        if ($withBooks) {
            $playlist->loadMissing(['books.category', 'books.author.user', 'books.image', 'books.images']);
            $data['books'] = $playlist->books->map(
                fn ($book) => BookApiPresenter::toArray($book, $user, [
                    'sort_order' => (int) ($book->pivot->sort_order ?? 0),
                    'playlist_book_id' => $book->pivot->id ?? null,
                ])
            )->values()->all();
        }

        return $data;
    }
}
