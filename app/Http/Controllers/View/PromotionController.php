<?php

namespace App\Http\Controllers\View;

use App\Models\Books;
use App\Models\Promotion;
use App\Support\AuthorScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Promotion::query()
            ->with(['book:id,title,price,author_id', 'creator:id,name'])
            ->latest();

        if (AuthorScope::isAuthorOnly($user)) {
            $authorId = AuthorScope::authorIdOrAbort($user);
            $query->whereHas('book', fn ($q) => $q->where('author_id', $authorId));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('book', fn ($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $promotions = $query->paginate(15)->withQueryString();

        return view('dashboard.promotions.index', compact('promotions'));
    }

    public function create(Request $request): View
    {
        return view('dashboard.promotions.create', [
            'books' => $this->bookOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $book = Books::findOrFail($data['book_id']);
        AuthorScope::ensureOwnsBook($request->user(), $book);

        Promotion::create([
            'book_id' => $book->id,
            'created_by' => $request->user()->id,
            'discount_percent' => $data['discount_percent'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('dashboard.promotions.index')
            ->with('success', 'Promotion created successfully.');
    }

    public function edit(Request $request, Promotion $promotion): View
    {
        AuthorScope::ensureOwnsBook($request->user(), $promotion->book);

        return view('dashboard.promotions.edit', [
            'promotion' => $promotion,
            'books' => $this->bookOptions($request),
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        AuthorScope::ensureOwnsBook($request->user(), $promotion->book);

        $data = $this->validateData($request);

        $book = Books::findOrFail($data['book_id']);
        AuthorScope::ensureOwnsBook($request->user(), $book);

        $promotion->update([
            'book_id' => $book->id,
            'discount_percent' => $data['discount_percent'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('dashboard.promotions.index')
            ->with('success', 'Promotion updated successfully.');
    }

    public function destroy(Request $request, Promotion $promotion): RedirectResponse
    {
        AuthorScope::ensureOwnsBook($request->user(), $promotion->book);

        $promotion->delete();

        return redirect()
            ->route('dashboard.promotions.index')
            ->with('success', 'Promotion deleted successfully.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'book_id' => ['required', 'string', 'exists:books,id'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:90'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function bookOptions(Request $request)
    {
        $user = $request->user();

        $query = Books::query()->whereNotNull('price')->where('price', '>', 0)->orderBy('title');

        if (AuthorScope::isAuthorOnly($user)) {
            $query->where('author_id', AuthorScope::authorIdOrAbort($user));
        }

        return $query->get(['id', 'title', 'price']);
    }
}
