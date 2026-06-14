<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use App\Models\Books;
use App\Models\Image;
use App\Models\User;
use App\Support\AuthorEarnings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AuthorsController
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $query = Author::query()
            ->with(['user', 'image'])
            ->withCount('books');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('bio', 'like', $like)
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        $authors = $query->latest('created_at')->paginate(10)->withQueryString();

        return view('dashboard.authors.index', compact('authors'));
    }

    public function create(): View
    {
        $users = User::orderBy('name')->get();
        $images = Image::orderBy('url')->get();

        return view('dashboard.authors.create', compact('users', 'images'));
    }

    public function store(StoreAuthorRequest $request): RedirectResponse
    {
        Author::create($request->validated());

        return redirect()->route('dashboard.authors.index')->with('success', 'Author created successfully');
    }

    public function show(Author $author): View
    {
        $author->load(['user', 'image', 'books' => fn ($q) => $q->latest()->limit(10)]);

        $earnings = $author->user
            ? AuthorEarnings::forUser($author->user)
            : AuthorEarnings::forAuthorId($author->id);

        return view('dashboard.authors.show', compact('author', 'earnings'));
    }

    public function edit(Author $author): View
    {
        $users = User::orderBy('name')->get();
        $images = Image::orderBy('url')->get();

        return view('dashboard.authors.edit', compact('author', 'users', 'images'));
    }

    public function update(UpdateAuthorRequest $request, Author $author): RedirectResponse
    {
        $author->update($request->validated());

        return redirect()->route('dashboard.authors.index')->with('success', 'Author updated successfully');
    }

    public function destroy(Author $author): RedirectResponse
    {
        $author->delete();

        return redirect()->route('dashboard.authors.index')->with('success', 'Author deleted successfully');
    }

    public function books(Request $request, Author $author): View
    {
        $query = Books::query()->where('author_id', $author->id);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $books = $query
            ->with(['category', 'image'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.authors.books', compact('author', 'books'));
    }
}