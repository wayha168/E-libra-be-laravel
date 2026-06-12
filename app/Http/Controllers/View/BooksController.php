<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreBooksRequest;
use App\Http\Requests\UpdateBooksRequest;
use App\Models\Author;
use App\Models\Books;
use App\Models\Category;
use App\Models\Image;
use App\Support\StoresUploadedFiles;
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
        $authors = Author::with('user')->orderBy('id')->get();

        return view('dashboard.books.create', compact('categories', 'images', 'authors'));
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

        if ($request->hasFile('pdf_file')) {
            $data['pdf_file'] = StoresUploadedFiles::storePdf($request->file('pdf_file'));
        }

        Books::create($data);

        return redirect()->route('dashboard.books.index')->with('success', 'Book created successfully');
    }

    public function show(Books $book): View
    {
        return view('dashboard.books.show', compact('book'));
    }

    public function edit(Books $book): View
    {
        $categories = Category::orderBy('name')->get();
        $images = Image::orderBy('url')->get();
        $authors = Author::with('user')->orderBy('id')->get();

        return view('dashboard.books.edit', compact('book', 'categories', 'images', 'authors'));
    }

    public function update(UpdateBooksRequest $request, Books $book): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            StoresUploadedImages::deleteById($book->image_id);
            $data['image_id'] = StoresUploadedImages::store(
                $request->file('image_file'),
                'book',
                $data['title'] ?? $book->title
            );
        }

        if ($request->hasFile('pdf_file')) {
            $data['pdf_file'] = StoresUploadedFiles::storePdf($request->file('pdf_file'));
        }

        $book->update($data);

        return redirect()->route('dashboard.books.index')->with('success', 'Book updated successfully');
    }

    public function destroy(Books $book): RedirectResponse
    {
        $book->delete();

        return redirect()->route('dashboard.books.index')->with('success', 'Book deleted successfully');
    }
}
