<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\Books;
use App\Models\Playlist;
use App\Models\UserSavedBook;
use App\Support\BookApiPresenter;
use App\Support\PlaylistApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BookSaveController extends Controller
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all saved books for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->integer('per_page', 15), 50);

        $cacheKey = "user.{$user->id}.saved_books.list.page_{$request->integer('page', 1)}";

        $savedBooks = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $perPage) {
            return UserSavedBook::query()
                ->where('user_id', $user->id)
                ->with(['book:id,title,description,price,author_id,category_id,image_id,status'])
                ->latest()
                ->paginate($perPage);
        });

        $savedBooks->getCollection()->transform(function ($saved) use ($user) {
            return array_merge(
                BookApiPresenter::toArray($saved->book, $user),
                ['saved_at' => $saved->created_at?->toIso8601String(), 'notes' => $saved->notes]
            );
        });

        return response()->json([
            'message' => 'Saved books fetched successfully',
            'data' => $savedBooks,
        ]);
    }

    /**
     * Save a book for the authenticated user.
     */
    public function save(Request $request, Books $book): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = UserSavedBook::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($existing) {
            // Update notes if provided
            if ($request->filled('notes')) {
                $existing->update(['notes' => $validated['notes']]);
            }

            return response()->json([
                'message' => 'Book already saved',
                'data' => [
                    'saved' => true,
                    'book_id' => $book->id,
                    'notes' => $existing->notes,
                ],
            ], 200);
        }

        // Create new saved book record
        UserSavedBook::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Invalidate cache
        $this->invalidateUserCache($user->id);

        $book->load(['category', 'author.user', 'image']);

        return response()->json([
            'message' => 'Book saved successfully',
            'data' => array_merge(
                BookApiPresenter::toArray($book, $user),
                ['saved' => true]
            ),
        ], 201);
    }

    /**
     * Unsave a book for the authenticated user.
     */
    public function unsave(Request $request, Books $book): JsonResponse
    {
        $user = $request->user();

        $saved = UserSavedBook::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if (!$saved) {
            return response()->json([
                'message' => 'Book was not saved',
                'data' => ['saved' => false],
            ], 200);
        }

        $saved->delete();

        // Invalidate cache
        $this->invalidateUserCache($user->id);

        return response()->json([
            'message' => 'Book unsaved successfully',
            'data' => ['saved' => false],
        ]);
    }

    /**
     * Toggle save status of a book.
     */
    public function toggle(Request $request, Books $book): JsonResponse
    {
        $user = $request->user();

        $saved = UserSavedBook::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($saved) {
            $saved->delete();
            $isSaved = false;
        } else {
            UserSavedBook::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
            $isSaved = true;
        }

        // Invalidate cache
        $this->invalidateUserCache($user->id);

        return response()->json([
            'message' => $isSaved ? 'Book saved' : 'Book unsaved',
            'data' => ['saved' => $isSaved],
        ]);
    }

    /**
     * Add a saved book to an existing playlist or create a new one.
     */
    public function addToPlaylist(Request $request, Books $book): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'playlist_id' => ['nullable', 'uuid', 'exists:playlists,id'],
            'playlist_name' => ['nullable', 'string', 'max:255'],
            'create_new' => ['sometimes', 'boolean'],
        ]);

        $playlist = null;

        // If no playlist_id but playlist_name provided, create a new playlist
        if (empty($validated['playlist_id']) && !empty($validated['playlist_name'])) {
            $playlist = Playlist::create([
                'user_id' => $user->id,
                'name' => $validated['playlist_name'],
                'is_public' => false, // Default to private
            ]);
        } elseif (!empty($validated['playlist_id'])) {
            // Find existing playlist
            $playlist = Playlist::find($validated['playlist_id']);

            // Verify user owns this playlist
            if (!$playlist->isOwnedBy($user)) {
                return response()->json([
                    'message' => 'You do not have permission to add books to this playlist',
                    'data' => null,
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'Either playlist_id or playlist_name must be provided',
                'data' => null,
            ], 422);
        }

        // Check if book is already in playlist
        if ($playlist->books()->where('books.id', $book->id)->exists()) {
            return response()->json([
                'message' => 'Book is already in this playlist',
                'data' => [
                    'playlist_id' => $playlist->id,
                    'book_id' => $book->id,
                ],
            ], 422);
        }

        // Add book to playlist
        $nextOrder = (int) $playlist->books()->max('playlist_books.sort_order') + 1;

        $playlist->books()->attach($book->id, [
            'id' => (string) Str::uuid(),
            'sort_order' => $nextOrder,
        ]);

        // Invalidate cache
        $this->invalidateUserCache($user->id);

        $playlist->load(['user:id,name,email'])->loadCount(['books', 'likes', 'comments']);

        return response()->json([
            'message' => 'Book added to playlist',
            'data' => PlaylistApiPresenter::toArray($playlist, $user, true),
        ], 201);
    }

    /**
     * Get offline cache metadata for all saved books.
     */
    public function offlineCache(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get cache key for user's saved books
        $cacheKey = "user.{$user->id}.offline_books";

        // Fetch saved books with their full data
        $savedBooks = UserSavedBook::query()
            ->where('user_id', $user->id)
            ->with(['book:id,title,description,price,author_id,category_id,image_id,pdf_file,pdf_preview_path,status'])
            ->latest()
            ->get();

        $offlineData = [
            'user_id' => $user->id,
            'synced_at' => now()->toIso8601String(),
            'books' => $savedBooks->map(function ($saved) use ($user) {
                $book = $saved->book;

                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'description' => $book->description,
                    'price' => $book->price,
                    'has_pdf' => !empty($book->pdf_file),
                    'pdf_file' => $book->pdf_file, // Include for offline access
                    'preview_path' => $book->pdf_preview_path,
                    'saved_at' => $saved->created_at?->toIso8601String(),
                    'notes' => $saved->notes,
                ];
            })->all(),
            'total_books' => $savedBooks->count(),
            'cache_ttl' => self::CACHE_TTL,
        ];

        // Store in cache
        Cache::put($cacheKey, $offlineData, self::CACHE_TTL);

        return response()->json([
            'message' => 'Offline cache data prepared',
            'data' => $offlineData,
        ]);
    }

    /**
     * Get a single saved book's offline data.
     */
    public function offlineBook(Request $request, Books $book): JsonResponse
    {
        $user = $request->user();

        $saved = UserSavedBook::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if (!$saved) {
            return response()->json([
                'message' => 'Book is not saved',
                'data' => null,
            ], 404);
        }

        $cacheKey = "user.{$user->id}.offline_book.{$book->id}";

        $offlineData = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($book, $saved) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'description' => $book->description,
                'price' => $book->price,
                'has_pdf' => !empty($book->pdf_file),
                'pdf_file' => $book->pdf_file,
                'preview_path' => $book->pdf_preview_path,
                'saved_at' => $saved->created_at?->toIso8601String(),
                'notes' => $saved->notes,
                'synced_at' => now()->toIso8601String(),
            ];
        });

        return response()->json([
            'message' => 'Book offline data fetched',
            'data' => $offlineData,
        ]);
    }

    /**
     * Check if a book is saved by the authenticated user.
     */
    public function isSaved(Request $request, Books $book): JsonResponse
    {
        $user = $request->user();

        $isSaved = UserSavedBook::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();

        return response()->json([
            'message' => 'Save status fetched',
            'data' => ['saved' => $isSaved],
        ]);
    }

    /**
     * Invalidate all cache keys for a user.
     */
    private function invalidateUserCache(string $userId): void
    {
        // Clear all related cache keys
        $pattern = "user.{$userId}.*";
        Cache::flush();

        // More targeted approach with tags if needed
        // Cache::tags(["user.{$userId}"])->flush();
    }
}
