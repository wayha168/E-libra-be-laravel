<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use App\Models\Books;
use App\Models\Image;
use App\Models\Role;
use App\Models\User;
use App\Support\AuthorEarnings;
use App\Support\StoresUploadedImages;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

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
        $users = User::query()
            ->with('role')
            ->whereDoesntHave('authorProfile')
            ->whereHas('role', fn ($q) => $q->whereIn('role', ['user', 'author']))
            ->orderBy('name')
            ->get();

        $images = Image::query()
            ->whereIn('image_type', ['author_profile', 'profile', 'general'])
            ->latest()
            ->limit(100)
            ->get();

        return view('dashboard.authors.create', compact('users', 'images'));
    }

    public function store(StoreAuthorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $mode = $data['mode'] ?? 'new_account';

        $imageId = $data['image_id'] ?? null;
        if ($request->hasFile('image_file')) {
            $imageId = StoresUploadedImages::store(
                $request->file('image_file'),
                'author_profile',
                $data['name'] ?? 'Author'
            );
        }

        $author = DB::transaction(function () use ($data, $mode, $imageId) {
            if ($mode === 'existing_user') {
                $user = User::with('role')->findOrFail($data['user_id']);
                $authorRoleId = Role::where('role', 'author')->value('id');

                if ($authorRoleId && ! $user->isAuthor()) {
                    $user->update(['role_id' => $authorRoleId]);
                }

                return Author::create([
                    'user_id' => $user->id,
                    'image_id' => $imageId,
                    'bio' => $data['bio'] ?? null,
                    'website' => $data['website'] ?? null,
                    'facebook' => $data['facebook'] ?? null,
                    'instagram' => $data['instagram'] ?? null,
                    'twitter' => $data['twitter'] ?? null,
                    'tiktok' => $data['tiktok'] ?? null,
                    'youtube' => $data['youtube'] ?? null,
                    'telegram' => $data['telegram'] ?? null,
                ]);
            }

            $authorRoleId = Role::where('role', 'author')->value('id');
            if (! $authorRoleId) {
                abort(422, 'Author role is not configured.');
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'confirm_password' => $data['password'],
                'role_id' => $authorRoleId,
                'status' => $data['status'] ?? 'active',
                'profile_image_id' => $imageId,
            ]);

            return Author::create([
                'user_id' => $user->id,
                'image_id' => $imageId,
                'bio' => $data['bio'] ?? null,
                'website' => $data['website'] ?? null,
                'facebook' => $data['facebook'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'twitter' => $data['twitter'] ?? null,
                'tiktok' => $data['tiktok'] ?? null,
                'youtube' => $data['youtube'] ?? null,
                'telegram' => $data['telegram'] ?? null,
            ]);
        });

        return redirect()
            ->route('dashboard.authors.show', $author)
            ->with('success', 'Author account created successfully');
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
        $users = User::query()
            ->with('role')
            ->where(function ($q) use ($author) {
                $q->whereDoesntHave('authorProfile')
                    ->orWhere('id', $author->user_id);
            })
            ->orderBy('name')
            ->get();

        $images = Image::orderBy('url')->get();

        return view('dashboard.authors.edit', compact('author', 'users', 'images'));
    }

    public function update(UpdateAuthorRequest $request, Author $author): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            $data['image_id'] = StoresUploadedImages::store(
                $request->file('image_file'),
                'author_profile',
                $author->user?->name ?? 'Author'
            );
        }

        $author->update($data);

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
