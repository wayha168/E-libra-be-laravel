<?php

namespace App\Http\Controllers\Api;

use App\Models\Author;
use App\Models\Books;
use App\Support\BookApiPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'type' => ['nullable', 'string', 'in:all,books,authors,top_selling'],
        ]);

        $q = trim((string) ($validated['q'] ?? $validated['query'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 10);
        $type = $validated['type'] ?? 'all';
        $user = auth('sanctum')->user();

        $data = [
            'books' => [],
            'authors' => [],
            'top_selling' => [],
        ];

        if ($type === 'all' || $type === 'books') {
            $data['books'] = $this->searchBooks($q, $limit, $user);
        }

        if ($type === 'all' || $type === 'authors') {
            $data['authors'] = $this->searchAuthors($q, $limit);
        }

        if ($type === 'all' || $type === 'top_selling') {
            $data['top_selling'] = $this->topSellingBooks($q, $limit, $user);
        }

        return response()->json([
            'message' => 'Search results fetched successfully',
            'data' => $data,
            'meta' => [
                'query' => $q,
                'limit' => $limit,
                'type' => $type,
                'counts' => [
                    'books' => count($data['books']),
                    'authors' => count($data['authors']),
                    'top_selling' => count($data['top_selling']),
                ],
            ],
        ]);
    }

    public function topSelling(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $q = trim((string) ($validated['q'] ?? $validated['query'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 10);
        $user = auth('sanctum')->user();

        $books = $this->topSellingBooks($q, $limit, $user);

        return response()->json([
            'message' => 'Top selling books fetched successfully',
            'data' => $books,
            'meta' => [
                'query' => $q,
                'limit' => $limit,
                'count' => count($books),
            ],
        ]);
    }

    private function searchBooks(string $q, int $limit, $user): array
    {
        $query = $this->booksBaseQuery();

        if ($q !== '') {
            $this->applyBookSearch($query, $q);
        } else {
            $query->latest();
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(fn (Books $book) => BookApiPresenter::toArray($book, $user))
            ->values()
            ->all();
    }

    private function searchAuthors(string $q, int $limit): array
    {
        $query = Author::query()
            ->with(['user.profileImage', 'image'])
            ->withCount('books');

        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function (Builder $builder) use ($like) {
                $builder->where('bio', 'like', $like)
                    ->orWhereHas('user', function (Builder $userQuery) use ($like) {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        } else {
            $query->orderByDesc('books_count');
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(fn (Author $author) => [
                'id' => $author->id,
                'user_id' => $author->user_id,
                'name' => $author->user?->name,
                'email' => $author->user?->email,
                'bio' => $author->bio,
                'website' => $author->website,
                'facebook' => $author->facebook,
                'instagram' => $author->instagram,
                'twitter' => $author->twitter,
                'tiktok' => $author->tiktok,
                'youtube' => $author->youtube,
                'telegram' => $author->telegram,
                'image_url' => $author->image?->url ?? $author->user?->profileImage?->url,
                'books_count' => (int) $author->books_count,
            ])
            ->values()
            ->all();
    }

    private function topSellingBooks(string $q, int $limit, $user): array
    {
        $query = $this->booksBaseQuery()
            ->orderByDesc('paid_purchases_count')
            ->orderByDesc('created_at');

        if ($q !== '') {
            $this->applyBookSearch($query, $q);
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(function (Books $book) use ($user) {
                return BookApiPresenter::toArray($book, $user, [
                    'sales_count' => (int) ($book->paid_purchases_count ?? 0),
                ]);
            })
            ->values()
            ->all();
    }

    private function booksBaseQuery(): Builder
    {
        return Books::query()
            ->published()
            ->with(['category', 'author.user', 'image', 'images'])
            ->withCount([
                'likes',
                'comments',
                'purchases as paid_purchases_count' => fn (Builder $q) => $q->where('status', 'paid'),
            ]);
    }

    private function applyBookSearch(Builder $query, string $q): void
    {
        $like = "%{$q}%";

        $query->where(function (Builder $builder) use ($like) {
            $builder->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', $like))
                ->orWhereHas('author.user', fn (Builder $authorQuery) => $authorQuery->where('name', 'like', $like));
        });
    }
}
