<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreBooksRequest;
use App\Http\Requests\UpdateBooksRequest;
use App\Models\Books;
use Illuminate\Http\Request;

class BooksController extends Controller
{
    public function index(Request $request)
    {
        $query = Books::query();

        // Filters
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($qc) use ($search) {
                        $qc->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->string('category_id')->toString());
        }

        if ($request->filled('public_date')) {
            $query->whereDate('public_date', $request->string('public_date')->toString());
        }

        return response()->json([

            'message' => 'Books fetched successfully',
            'data' => $query->latest()->paginate(10),
        ]);
    }

    public function store(StoreBooksRequest $request)
    {
        $book = Books::create($request->validated());

        return response()->json([
            'message' => 'Book created successfully',
            'data' => $book,
        ], 201);
    }

    public function show(Books $book)
    {
        return response()->json([
            'message' => 'Book fetched successfully',
            'data' => $book,
        ]);
    }

    public function update(UpdateBooksRequest $request, Books $book)
    {
        $book->update($request->validated());

        return response()->json([
            'message' => 'Book updated successfully',
            'data' => $book,
        ]);
    }

    public function destroy(Books $book)
    {
        $book->delete();

        return response()->json([
            'message' => 'Book deleted successfully',
            'data' => null,
        ]);
    }
}
