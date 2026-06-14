<?php

namespace App\Support;

use App\Models\BookComment;
use App\Models\BookLike;
use App\Models\Books;

class BookApiPresenter
{
    public static function toArray(Books $book, $user = null, array $extra = []): array
    {
        $book->loadMissing(['category', 'author.user', 'image', 'images']);

        $paid = BookAccess::isPaid($book);
        $hasPdf = BookAccess::hasPdf($book);
        $fullAccess = BookAccess::canAccessFull($user, $book);

        return array_merge([
            'id' => $book->id,
            'title' => $book->title,
            'description' => $book->description,
            'price' => $book->price,
            'public_date' => $book->public_date?->toDateString(),
            'category' => $book->category ? [
                'id' => $book->category->id,
                'name' => $book->category->name,
            ] : null,
            'author' => $book->author?->user ? [
                'id' => $book->author->user->id,
                'name' => $book->author->user->name,
            ] : null,
            'image_url' => $book->image?->url,
            'images' => $book->relationLoaded('images')
                ? $book->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->url,
                    'alt_text' => $img->alt_text,
                ])->values()->all()
                : [],
            'has_pdf' => $hasPdf,
            'has_full_access' => $fullAccess,
            'can_preview' => $paid && $hasPdf && !$fullAccess,
            'trial_pages' => BookAccess::trialPages(),
            'preview_url' => ($paid && $hasPdf) ? url('/api/v1/books/' . $book->id . '/preview') : null,
            'download_url' => ($hasPdf && $fullAccess) ? url('/api/v1/books/' . $book->id . '/download') : null,
            'read_url' => $hasPdf ? url('/dashboard/books/' . $book->id . '/read') : null,
            'show_url' => url('/dashboard/books/' . $book->id),
            'likes_count' => (int) ($book->likes_count ?? BookLike::where('book_id', $book->id)->count()),
            'comments_count' => (int) ($book->comments_count ?? BookComment::where('book_id', $book->id)->count()),
            'user_has_liked' => $user
                ? BookLike::where('book_id', $book->id)->where('user_id', $user->id)->exists()
                : false,
            'purchase_count' => (int) ($book->paid_purchases_count ?? 0),
        ], $extra);
    }

    public static function recommendation(Books $book, $user = null): array
    {
        return self::toArray($book, $user, [
            'reason' => $book->recommendation_reason ?? 'Recommended for you',
            'score' => (int) ($book->recommendation_score ?? 0),
        ]);
    }
}
