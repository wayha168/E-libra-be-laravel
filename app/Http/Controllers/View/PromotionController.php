<?php

namespace App\Http\Controllers\View;

use App\Models\Author;
use App\Models\Books;
use App\Models\Promotion;
use App\Support\AuthorScope;
use App\Support\PromotionNotificationHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionController
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Promotion::query()
            ->with(['book:id,title,price,author_id', 'author.user:id,name', 'creator:id,name'])
            ->latest();

        if (AuthorScope::isAuthorOnly($user)) {
            $authorId = AuthorScope::authorIdOrAbort($user);
            $query->where(function ($q) use ($authorId) {
                $q->where('author_id', $authorId)
                    ->orWhereHas('book', fn ($b) => $b->where('author_id', $authorId));
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->whereHas('book', fn ($b) => $b->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('author.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $promotions = $query->paginate(15)->withQueryString();

        return view('dashboard.promotions.index', compact('promotions'));
    }

    public function create(Request $request): View
    {
        return view('dashboard.promotions.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $this->authorizePayload($request, $data);

        $promotion = Promotion::create($this->payload($request, $data));
        PromotionNotificationHandler::handleCreated($promotion->fresh());

        return redirect()
            ->route('dashboard.promotions.index')
            ->with('success', 'Promotion created successfully.');
    }

    public function edit(Request $request, Promotion $promotion): View
    {
        $this->authorizePromotion($request->user(), $promotion);

        return view('dashboard.promotions.edit', array_merge(
            $this->formData($request),
            ['promotion' => $promotion->load(['book', 'author.user'])]
        ));
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->authorizePromotion($request->user(), $promotion);

        $data = $this->validateData($request, $promotion);
        $this->authorizePayload($request, $data);

        $promotion->update($this->payload($request, $data));

        return redirect()
            ->route('dashboard.promotions.index')
            ->with('success', 'Promotion updated successfully.');
    }

    public function destroy(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->authorizePromotion($request->user(), $promotion);

        $promotion->delete();

        return redirect()
            ->route('dashboard.promotions.index')
            ->with('success', 'Promotion deleted successfully.');
    }

    private function formData(Request $request): array
    {
        return [
            'books' => $this->bookOptions($request),
            'authors' => $this->authorOptions($request),
            'isAuthorOnly' => AuthorScope::isAuthorOnly($request->user()),
            'ownAuthorId' => AuthorScope::authorId($request->user()),
        ];
    }

    private function validateData(Request $request, ?Promotion $promotion = null): array
    {
        $data = $request->validate([
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

        if ($data['scope'] === 'book' && empty($data['book_id'])) {
            throw ValidationException::withMessages(['book_id' => 'Please select a book.']);
        }

        if ($data['scope'] === 'author' && empty($data['author_id'])) {
            throw ValidationException::withMessages(['author_id' => 'Please select an author.']);
        }

        return $data;
    }

    private function payload(Request $request, array $data): array
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
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function authorizePayload(Request $request, array $data): void
    {
        $user = $request->user();

        if ($data['scope'] === 'book') {
            $book = Books::findOrFail($data['book_id']);
            AuthorScope::ensureOwnsBook($user, $book);

            return;
        }

        $authorId = $data['author_id'];
        if (AuthorScope::isAuthorOnly($user) && AuthorScope::authorIdOrAbort($user) !== $authorId) {
            abort(403, 'You can only create promotions for your own author profile.');
        }
    }

    private function authorizePromotion($user, Promotion $promotion): void
    {
        if ($promotion->book_id && $promotion->book) {
            AuthorScope::ensureOwnsBook($user, $promotion->book);

            return;
        }

        if ($promotion->author_id && AuthorScope::isAuthorOnly($user)) {
            if (AuthorScope::authorIdOrAbort($user) !== $promotion->author_id) {
                abort(403, 'You can only manage your own promotions.');
            }
        }
    }

    private function bookOptions(Request $request)
    {
        $user = $request->user();
        $query = Books::query()->whereNotNull('price')->where('price', '>', 0)->orderBy('title');

        if (AuthorScope::isAuthorOnly($user)) {
            $query->where('author_id', AuthorScope::authorIdOrAbort($user));
        }

        return $query->get(['id', 'title', 'price', 'author_id']);
    }

    private function authorOptions(Request $request)
    {
        $user = $request->user();

        if (AuthorScope::isAuthorOnly($user)) {
            $authorId = AuthorScope::authorIdOrAbort($user);

            return Author::query()->with('user:id,name')->where('id', $authorId)->get();
        }

        return Author::query()->with('user:id,name')->orderBy('created_at')->get();
    }
}
