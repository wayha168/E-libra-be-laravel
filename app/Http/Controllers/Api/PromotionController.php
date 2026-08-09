<?php

namespace App\Http\Controllers\Api;

use App\Models\Author;
use App\Models\Books;
use App\Models\Promotion;
use App\Support\AuthorScope;
use App\Support\PromotionNotificationHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Optional auth (public route) — Bearer token still resolves the user when present
        $user = $request->user('sanctum') ?? $request->user();

        $query = Promotion::query()
            ->with(['book:id,title,price,author_id', 'author.user:id,name', 'creator:id,name'])
            ->latest();

        $canManage = $user
            && (method_exists($user, 'isAdmin') && ($user->isAdmin() || $user->isSuperAdmin() || $user->isAuthor()));

        // Guests and regular users only see currently active promotions (like public comments/likes)
        if (! $canManage) {
            $query->active();
        } elseif (AuthorScope::isAuthorOnly($user)) {
            $authorId = AuthorScope::authorIdOrAbort($user);
            $query->where(function ($q) use ($authorId) {
                $q->where('author_id', $authorId)
                    ->orWhereHas('book', fn ($b) => $b->where('author_id', $authorId));
            });
        }

        if ($request->filled('book_id')) {
            $bookId = $request->string('book_id')->toString();
            $bookAuthorId = Books::whereKey($bookId)->value('author_id');

            $query->where(function ($q) use ($bookId, $bookAuthorId) {
                $q->where('book_id', $bookId);

                // Author-scoped promotions also apply to that author's books
                if ($bookAuthorId) {
                    $q->orWhere(function ($authorScope) use ($bookAuthorId) {
                        $authorScope->whereNull('book_id')
                            ->where('author_id', $bookAuthorId);
                    });
                }
            });
        }

        if ($request->filled('author_id')) {
            $query->where('author_id', $request->string('author_id')->toString());
        }

        return response()->json([
            'message' => 'Promotions fetched successfully',
            'data' => $query->paginate(15),
        ]);
    }

    public function show(Request $request, Promotion $promotion): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        $canManage = $user
            && (method_exists($user, 'isAdmin') && ($user->isAdmin() || $user->isSuperAdmin() || $user->isAuthor()));

        if (! $canManage && ! $promotion->isCurrentlyActive()) {
            return response()->json([
                'message' => 'Promotion not found.',
            ], 404);
        }

        if ($canManage && AuthorScope::isAuthorOnly($user)) {
            $authorId = AuthorScope::authorIdOrAbort($user);
            $owns = $promotion->author_id === $authorId
                || ($promotion->book && $promotion->book->author_id === $authorId);

            if (! $owns && ! $promotion->isCurrentlyActive()) {
                return response()->json([
                    'message' => 'Promotion not found.',
                ], 404);
            }
        }

        $promotion->load(['book:id,title,price,author_id', 'author.user:id,name', 'creator:id,name']);

        return response()->json([
            'message' => 'Promotion fetched successfully',
            'data' => $promotion,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $this->authorizePayload($request, $data);

        $promotion = Promotion::create($this->makePayload($request, $data));
        PromotionNotificationHandler::handleCreated($promotion->fresh());

        return response()->json([
            'message' => 'Promotion created successfully',
            'data' => $promotion->load(['book:id,title,price,author_id', 'author.user:id,name', 'creator:id,name']),
        ], 201);
    }

    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorizePromotion($request, $promotion);

        $data = $this->validatePayload($request, $promotion);
        $this->authorizePayload($request, $data);

        $promotion->fill($this->makePayload($request, $data));
        $promotion->save();

        return response()->json([
            'message' => 'Promotion updated successfully',
            'data' => $promotion->fresh()->load(['book:id,title,price,author_id', 'author.user:id,name', 'creator:id,name']),
        ]);
    }

    public function destroy(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorizePromotion($request, $promotion);

        $promotion->delete();

        return response()->json([
            'message' => 'Promotion deleted successfully',
            'data' => null,
        ]);
    }

    private function validatePayload(Request $request, ?Promotion $promotion = null): array
    {
        return $request->validate([
            'type' => ['required', Rule::in([Promotion::TYPE_PERCENTAGE, Promotion::TYPE_FREE_TRIAL])],
            'scope' => ['required', Rule::in(['book', 'author'])],
            'book_id' => ['nullable', 'string', 'exists:books,id', Rule::requiredIf(fn () => $request->input('scope') === 'book')],
            'author_id' => ['nullable', 'string', 'exists:authors,id', Rule::requiredIf(fn () => $request->input('scope') === 'author')],
            'discount_percent' => [
                Rule::requiredIf(fn () => $request->input('type') === Promotion::TYPE_PERCENTAGE),
                'nullable',
                'integer',
                'min:1',
                'max:90',
            ],
            'trial_days' => [
                Rule::requiredIf(fn () => $request->input('type') === Promotion::TYPE_FREE_TRIAL),
                'nullable',
                'integer',
                'min:1',
                'max:30',
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function makePayload(Request $request, array $data): array
    {
        $isTrial = $data['type'] === Promotion::TYPE_FREE_TRIAL;

        return [
            'type' => $data['type'],
            'book_id' => $data['scope'] === 'book' ? $data['book_id'] : null,
            'author_id' => $data['scope'] === 'author' ? $data['author_id'] : null,
            'created_by' => $request->user()->id,
            'discount_percent' => $isTrial ? null : (int) $data['discount_percent'],
            'trial_days' => $isTrial ? (int) ($data['trial_days'] ?? 7) : null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ];
    }

    private function authorizePayload(Request $request, array $data): void
    {
        $user = $request->user();

        if ($data['scope'] === 'book') {
            $this->authorizeBook($request, Books::findOrFail($data['book_id']));

            return;
        }

        if (AuthorScope::isAuthorOnly($user) && AuthorScope::authorIdOrAbort($user) !== $data['author_id']) {
            throw ValidationException::withMessages([
                'author_id' => 'You can only manage promotions for your own author profile.',
            ])->status(403);
        }

        if (! Author::whereKey($data['author_id'])->exists()) {
            throw ValidationException::withMessages(['author_id' => 'Author not found.']);
        }
    }

    private function authorizePromotion(Request $request, Promotion $promotion): void
    {
        if ($promotion->book) {
            $this->authorizeBook($request, $promotion->book);

            return;
        }

        $user = $request->user();
        if (AuthorScope::isAuthorOnly($user) && AuthorScope::authorIdOrAbort($user) !== $promotion->author_id) {
            throw ValidationException::withMessages([
                'author_id' => 'You can only manage your own promotions.',
            ])->status(403);
        }
    }

    private function authorizeBook(Request $request, ?Books $book): void
    {
        $user = $request->user();

        if (! $book) {
            abort(404, 'Book not found.');
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        if (AuthorScope::isAuthorOnly($user) && $user->authorProfile && $user->authorProfile->id === $book->author_id) {
            return;
        }

        throw ValidationException::withMessages([
            'book_id' => 'You can only manage promotions for your own books.',
        ])->status(403);
    }
}
