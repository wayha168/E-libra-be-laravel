<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Api\DashboardOverviewController;
use App\Http\Requests\StoreBooksRequest;
use App\Http\Requests\UpdateBooksRequest;
use App\Models\Author;
use App\Models\Books;
use App\Models\Category;
use App\Models\Image;
use App\Support\BookAccess;
use App\Support\BookPdfStorage;
use App\Support\BookRecommendationService;
use App\Support\StoresUploadedFiles;
use App\Support\StoresUploadedImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BooksController
{
    public function index(Request $request): View
    {
        $query = Books::query()
            ->with(['author.user', 'category'])
            ->withCount(['likes', 'comments']);

        $user = $request->user();
        if ($user->isAuthor() && !$user->isAdmin() && !$user->isSuperAdmin()) {
            $authorId = $user->authorProfile?->id;
            $query->where('author_id', $authorId ?? '00000000-0000-0000-0000-000000000000');
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('author.user', fn ($uq) => $uq->where('name', 'like', $like))
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', $like));
            });
        }

        $books = $query->latest()->paginate(10)->withQueryString();
        $isAuthorView = $user->isAuthor() && !$user->isAdmin() && !$user->isSuperAdmin();

        return view('dashboard.books.index', compact('books', 'isAuthorView'));
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

        $imageIds = [];

        if ($request->hasFile('image_file')) {
            $imageIds[] = StoresUploadedImages::store(
                $request->file('image_file'),
                'book',
                $data['title'] ?? null
            );
        }

        if ($request->hasFile('image_files')) {
            $imageIds = array_merge(
                $imageIds,
                StoresUploadedImages::storeMany($request->file('image_files'), 'book', $data['title'] ?? null)
            );
        }

        $imageIds = array_values(array_filter($imageIds));
        if ($imageIds !== []) {
            $data['image_id'] = $imageIds[0];
        }

        if ($request->hasFile('pdf_file')) {
            $pdf = StoresUploadedFiles::storePdf($request->file('pdf_file'));
            if ($pdf) {
                $data['pdf_file'] = $pdf['pdf_file'];
                $data['pdf_preview_path'] = $pdf['pdf_preview_path'];
            }
        }

        $book = Books::create($data);

        StoresUploadedImages::attachToBook($book, $imageIds);

        BookRecommendationService::notifyInterestedUsers($book->load('category'));

        DashboardOverviewController::broadcastStats();

        return redirect()->route('dashboard.books.index')->with('success', 'Book created successfully');
    }

    public function show(Books $book): View
    {
        $book->load(['author.user', 'category', 'image', 'images']);
        $book->loadCount(['likes', 'comments']);

        $hasPdf = BookAccess::hasPdf($book);

        return view('dashboard.books.show', compact('book', 'hasPdf'));
    }

    public function read(Request $request, Books $book): View
    {
        $user = $request->user();
        BookAccess::appendAccessMeta($book, $user);

        if (!$book->has_pdf) {
            abort(404, 'This book has no PDF to read.');
        }

        return view('dashboard.books.read', compact('book'));
    }

    public function pdf(Request $request, Books $book)
    {
        $user = $request->user();

        if (!BookAccess::canAccessFull($user, $book)) {
            abort(403, 'Purchase or subscribe to access the full PDF.');
        }

        $path = BookPdfStorage::resolveFullPath($book);
        if (!$path) {
            abort(404, 'PDF not found.');
        }

        return BookPdfStorage::streamFile($path, \Illuminate\Support\Str::slug($book->title) . '.pdf', true);
    }

    public function edit(Books $book): View
    {
        $book->load('images');

        $categories = Category::orderBy('name')->get();
        $images = Image::orderBy('url')->get();
        $authors = Author::with('user')->orderBy('id')->get();

        return view('dashboard.books.edit', compact('book', 'categories', 'images', 'authors'));
    }

    public function update(UpdateBooksRequest $request, Books $book): RedirectResponse
    {
        $data = $request->validated();

        $newImageIds = [];

        if ($request->hasFile('image_file')) {
            $newImageIds[] = StoresUploadedImages::store(
                $request->file('image_file'),
                'book',
                $data['title'] ?? $book->title
            );
        }

        if ($request->hasFile('image_files')) {
            $newImageIds = array_merge(
                $newImageIds,
                StoresUploadedImages::storeMany($request->file('image_files'), 'book', $data['title'] ?? $book->title)
            );
        }

        $newImageIds = array_values(array_filter($newImageIds));
        if ($newImageIds !== [] && !$book->image_id) {
            $data['image_id'] = $newImageIds[0];
        }

        if ($request->hasFile('pdf_file')) {
            $pdf = StoresUploadedFiles::storePdf($request->file('pdf_file'));
            if ($pdf) {
                $data['pdf_file'] = $pdf['pdf_file'];
                $data['pdf_preview_path'] = $pdf['pdf_preview_path'];
            }
        }

        $book->update($data);

        StoresUploadedImages::attachToBook($book->fresh(), $newImageIds);

        return redirect()->route('dashboard.books.index')->with('success', 'Book updated successfully');
    }

    public function destroy(Books $book): RedirectResponse
    {
        $book->delete();

        return redirect()->route('dashboard.books.index')->with('success', 'Book deleted successfully');
    }
}
