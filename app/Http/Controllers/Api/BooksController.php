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

        $user = auth('sanctum')->user();
        $books = $query->latest()->paginate(10);

        $books->getCollection()->transform(function ($book) use ($user) {
            if (!$this->canAccessBook($user, $book)) {
                $book->pdf_file = null;
            }
            return $book;
        });

        return response()->json([
            'message' => 'Books fetched successfully',
            'data' => $books,
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
        $user = auth('sanctum')->user();
        if (!$this->canAccessBook($user, $book)) {
            $book->pdf_file = null;
        }

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

    public function buy(Request $request, Books $book)
    {
        $user = $request->user();

        if (is_null($book->price) || $book->price <= 0) {
            return response()->json([
                'message' => 'This book is free, no purchase required.',
            ], 400);
        }

        $existing = \App\Models\UserBuyBook::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($existing && $existing->status === 'paid') {
            return response()->json([
                'message' => 'You have already purchased this book.',
                'data' => $existing,
            ]);
        }

        $purchase = \App\Models\UserBuyBook::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => $book->price,
            'status' => 'paid',
            'purchased_at' => now(),
        ]);

        return response()->json([
            'message' => 'Book purchased successfully',
            'data' => $purchase,
        ], 201);
    }

    public function download(Request $request, Books $book)
    {
        $user = $request->user();

        if (!$this->canAccessBook($user, $book)) {
            return response()->json([
                'message' => 'You must purchase this book or subscribe to access it.',
            ], 403);
        }

        if (!$book->pdf_file) {
            return response()->json([
                'message' => 'This book does not have a PDF file available.',
            ], 404);
        }

        return redirect()->away($book->pdf_file);
    }

    private function canAccessBook($user, Books $book): bool
    {
        if (is_null($book->price) || $book->price <= 0) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if ($book->author_id && method_exists($user, 'authorProfile') && $user->authorProfile && $user->authorProfile->id === $book->author_id) {
            return true;
        }

        if ($user->user_subscribe) {
            return true;
        }

        return \App\Models\UserBuyBook::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'paid')
            ->exists();
    }
}
