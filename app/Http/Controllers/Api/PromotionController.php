<?php

namespace App\Http\Controllers\Api;

use App\Models\Books;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Promotion::query()
            ->with(['book:id,title,price,author_id', 'creator:id,name'])
            ->latest();

        if ($this->isAuthorOnly($user)) {
            $authorId = $user->authorProfile?->id;
            $query->whereHas('book', fn ($q) => $q->where('author_id', $authorId));
        }

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->string('book_id')->toString());
        }

        return response()->json([
            'message' => 'Promotions fetched successfully',
            'data' => $query->paginate(15),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        $book = Books::findOrFail($data['book_id']);
        $this->authorizeBook($request, $book);

        $promotion = Promotion::create([
            'book_id' => $book->id,
            'created_by' => $request->user()->id,
            'discount_percent' => $data['discount_percent'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Promotion created successfully',
            'data' => $promotion->load(['book:id,title,price,author_id', 'creator:id,name']),
        ], 201);
    }

    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorizeBook($request, $promotion->book);

        $data = $this->validatePayload($request, $promotion);

        if (array_key_exists('book_id', $data) && $data['book_id'] !== $promotion->book_id) {
            $book = Books::findOrFail($data['book_id']);
            $this->authorizeBook($request, $book);
            $promotion->book_id = $book->id;
        }

        $promotion->fill([
            'discount_percent' => $data['discount_percent'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? $promotion->is_active,
        ]);
        $promotion->save();

        return response()->json([
            'message' => 'Promotion updated successfully',
            'data' => $promotion->fresh()->load(['book:id,title,price,author_id', 'creator:id,name']),
        ]);
    }

    public function destroy(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorizeBook($request, $promotion->book);

        $promotion->delete();

        return response()->json([
            'message' => 'Promotion deleted successfully',
            'data' => null,
        ]);
    }

    private function validatePayload(Request $request, ?Promotion $promotion = null): array
    {
        return $request->validate([
            'book_id' => [$promotion ? 'sometimes' : 'required', 'string', 'exists:books,id'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:90'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeBook(Request $request, ?Books $book): void
    {
        $user = $request->user();

        if (!$book) {
            abort(404, 'Book not found.');
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        if ($this->isAuthorOnly($user) && $user->authorProfile && $user->authorProfile->id === $book->author_id) {
            return;
        }

        throw ValidationException::withMessages([
            'book_id' => 'You can only manage promotions for your own books.',
        ])->status(403);
    }

    private function isAuthorOnly($user): bool
    {
        return method_exists($user, 'isAuthor')
            && $user->isAuthor()
            && !(method_exists($user, 'isAdmin') && $user->isAdmin())
            && !(method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
    }
}
