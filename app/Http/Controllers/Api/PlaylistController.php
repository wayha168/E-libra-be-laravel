<?php

namespace App\Http\Controllers\Api;

use App\Models\Books;
use App\Models\Playlist;
use App\Support\PlaylistApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaylistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $perPage = min((int) $request->integer('per_page', 15), 50);

        $query = Playlist::query()
            ->with(['user:id,name,email'])
            ->withCount(['books', 'likes', 'comments'])
            ->visibleTo($user)
            ->latest();

        if ($request->filled('search')) {
            $search = '%' . $request->string('search')->toString() . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if ($request->boolean('mine') && $user) {
            $query->where('user_id', $user->id);
        }

        $playlists = $query->paginate($perPage);
        $playlists->getCollection()->transform(
            fn (Playlist $playlist) => PlaylistApiPresenter::toArray($playlist, $user)
        );

        return response()->json([
            'message' => 'Playlists fetched successfully',
            'data' => $playlists,
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $request->merge(['mine' => true]);

        return $this->index($request);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['sometimes', 'boolean'],
            'book_ids' => ['sometimes', 'array'],
            'book_ids.*' => ['uuid', 'exists:books,id'],
        ]);

        $playlist = Playlist::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? true,
        ]);

        if (!empty($validated['book_ids'])) {
            $this->syncBooks($playlist, $validated['book_ids']);
        }

        $playlist->load(['user:id,name,email'])->loadCount(['books', 'likes', 'comments']);

        return response()->json([
            'message' => 'Playlist created successfully',
            'data' => PlaylistApiPresenter::toArray($playlist, $request->user(), true),
        ], 201);
    }

    public function show(Request $request, Playlist $playlist): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$playlist->canBeViewedBy($user)) {
            return response()->json([
                'message' => 'Playlist not found or private',
                'data' => null,
            ], 404);
        }

        // Count a view when someone opens the playlist
        $playlist->increment('views_count');
        $playlist->refresh();

        $playlist->load(['user:id,name,email'])->loadCount(['books', 'likes', 'comments']);

        return response()->json([
            'message' => 'Playlist fetched successfully',
            'data' => PlaylistApiPresenter::toArray($playlist, $user, true),
        ]);
    }

    public function update(Request $request, Playlist $playlist): JsonResponse
    {
        if (!$playlist->canBeEditedBy($request->user())) {
            return response()->json([
                'message' => 'You do not have permission to edit this playlist',
                'data' => null,
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $playlist->update($validated);
        $playlist->load(['user:id,name,email'])->loadCount(['books', 'likes', 'comments']);

        return response()->json([
            'message' => 'Playlist updated successfully',
            'data' => PlaylistApiPresenter::toArray($playlist, $request->user(), true),
        ]);
    }

    public function destroy(Request $request, Playlist $playlist): JsonResponse
    {
        if (!$playlist->canBeDeletedBy($request->user())) {
            return response()->json([
                'message' => 'You do not have permission to delete this playlist',
                'data' => null,
            ], 403);
        }

        DB::transaction(function () use ($playlist) {
            $playlist->likes()->delete();
            $playlist->comments()->delete();
            $playlist->books()->detach();
            $playlist->delete();
        });

        return response()->json([
            'message' => 'Playlist deleted successfully',
            'data' => null,
        ]);
    }

    public function addBook(Request $request, Playlist $playlist): JsonResponse
    {
        if (!$playlist->canBeEditedBy($request->user())) {
            return response()->json([
                'message' => 'You do not have permission to edit this playlist',
                'data' => null,
            ], 403);
        }

        $validated = $request->validate([
            'book_id' => ['required', 'uuid', 'exists:books,id'],
        ]);

        if ($playlist->books()->where('books.id', $validated['book_id'])->exists()) {
            return response()->json([
                'message' => 'Book is already in this playlist',
                'data' => PlaylistApiPresenter::toArray($playlist->fresh()->loadCount(['books', 'likes', 'comments']), $request->user(), true),
            ], 422);
        }

        $nextOrder = (int) $playlist->books()->max('playlist_books.sort_order') + 1;

        $playlist->books()->attach($validated['book_id'], [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'sort_order' => $nextOrder,
        ]);

        $playlist->load(['user:id,name,email'])->loadCount(['books', 'likes', 'comments']);

        return response()->json([
            'message' => 'Book added to playlist',
            'data' => PlaylistApiPresenter::toArray($playlist, $request->user(), true),
        ], 201);
    }

    public function removeBook(Request $request, Playlist $playlist, Books $book): JsonResponse
    {
        if (!$playlist->canBeEditedBy($request->user())) {
            return response()->json([
                'message' => 'You do not have permission to edit this playlist',
                'data' => null,
            ], 403);
        }

        $playlist->books()->detach($book->id);
        $playlist->load(['user:id,name,email'])->loadCount(['books', 'likes', 'comments']);

        return response()->json([
            'message' => 'Book removed from playlist',
            'data' => PlaylistApiPresenter::toArray($playlist, $request->user(), true),
        ]);
    }

    public function reorderBooks(Request $request, Playlist $playlist): JsonResponse
    {
        if (!$playlist->canBeEditedBy($request->user())) {
            return response()->json([
                'message' => 'You do not have permission to edit this playlist',
                'data' => null,
            ], 403);
        }

        $validated = $request->validate([
            'book_ids' => ['required', 'array', 'min:1'],
            'book_ids.*' => ['uuid', 'exists:books,id'],
        ]);

        $existing = $playlist->books()->pluck('books.id')->all();
        $incoming = $validated['book_ids'];

        sort($existing);
        $sortedIncoming = $incoming;
        sort($sortedIncoming);

        if ($existing !== $sortedIncoming) {
            return response()->json([
                'message' => 'book_ids must include exactly the books currently in the playlist',
                'data' => null,
            ], 422);
        }

        foreach ($incoming as $index => $bookId) {
            $playlist->books()->updateExistingPivot($bookId, ['sort_order' => $index]);
        }

        $playlist->load(['user:id,name,email'])->loadCount(['books', 'likes', 'comments']);

        return response()->json([
            'message' => 'Playlist books reordered successfully',
            'data' => PlaylistApiPresenter::toArray($playlist, $request->user(), true),
        ]);
    }

    private function syncBooks(Playlist $playlist, array $bookIds): void
    {
        $sync = [];
        foreach (array_values(array_unique($bookIds)) as $index => $bookId) {
            $sync[$bookId] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'sort_order' => $index,
            ];
        }
        $playlist->books()->sync($sync);
    }
}
