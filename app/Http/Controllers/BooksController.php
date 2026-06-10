<?php

namespace App\Http\Controllers;

use App\Models\Books;
use App\Http\Requests\StoreBooksRequest;
use App\Http\Requests\UpdateBooksRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BooksController
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('dashboard.books.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBooksRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Books::create($data);

        return redirect()->route('dashboard.books.index')->with('success', 'Book created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Books $books): View
    {
        return view('dashboard.books.show', compact('books'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Books $books): View
    {
        return view('dashboard.books.edit', compact('books'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBooksRequest $request, Books $books): RedirectResponse
    {
        $data = $request->validated();
        $books->update($data);

        return redirect()->route('dashboard.books.index')->with('success', 'Book updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Books $books): RedirectResponse
    {
        $books->delete();

        return redirect()->route('dashboard.books.index')->with('success', 'Book deleted successfully');
    }
}
