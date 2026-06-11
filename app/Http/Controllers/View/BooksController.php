<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreBooksRequest;
use App\Http\Requests\UpdateBooksRequest;
use App\Models\Books;
use App\Models\Category;
use App\Models\Image;
use App\Support\StoresUploadedImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BooksController
{
    public function index(Request $request): View
    {
        $query = Books::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where('title', 'like', $like)
                    ->orWhere('author_name', 'like', $like)
                    ->orWhere('isbn', 'like', $like);
            });
        }

        $books = $query->latest()->paginate(10);

        return view('dashboard.books.index', compact('books'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $images = Image::orderBy('url')->get();

        return view('dashboard.books.create', compact('categories', 'images'));
    }

    public function store(StoreBooksRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            $data['image_id'] = StoresUploadedImages::store(
                $request->file('image_file'),
                'book',
                $data['title'] ?? null
            );
        }

        Books::create($data);

        return redirect()->route('dashboard.books.index')->with('success', 'Book created successfully');
    }

    public function show(Books $books): View
    {
        return view('dashboard.books.show', compact('books'));
    }

    public function edit(Books $books): View
    {
        $categories = Category::orderBy('name')->get();
        $images = Image::orderBy('url')->get();

        return view('dashboard.books.edit', compact('books', 'categories', 'images'));
    }

    public function update(UpdateBooksRequest $request, Books $books): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            StoresUploadedImages::deleteById($books->image_id);
            $data['image_id'] = StoresUploadedImages::store(
                $request->file('image_file'),
                'book',
                $data['title'] ?? $books->title
            );
        }

        $books->update($data);

        return redirect()->route('dashboard.books.index')->with('success', 'Book updated successfully');
    }

    public function destroy(Books $books): RedirectResponse
    {
        $books->delete();

        return redirect()->route('dashboard.books.index')->with('success', 'Book deleted successfully');
    }
}
