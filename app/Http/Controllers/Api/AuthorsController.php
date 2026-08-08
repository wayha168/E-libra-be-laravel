<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use App\Models\Role;
use App\Models\User;
use App\Support\StoresUploadedImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthorsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Author::query()
            ->with(['user:id,name,email,status', 'image'])
            ->withCount('books');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('bio', 'like', $like)
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        $authors = $query->latest('created_at')->paginate(15);
        $authors->getCollection()->transform(fn (Author $author) => $this->present($author));

        return response()->json([
            'message' => 'Authors fetched successfully',
            'data' => $authors,
        ]);
    }

    public function show(Author $author): JsonResponse
    {
        $author->load(['user:id,name,email,status', 'image'])->loadCount('books');

        return response()->json([
            'message' => 'Author fetched successfully',
            'data' => $this->present($author),
        ]);
    }

    public function store(StoreAuthorRequest $request): JsonResponse
    {
        $data = $request->validated();
        $mode = $data['mode'] ?? 'new_account';
        $social = $this->socialFrom($data);

        $imageId = $data['image_id'] ?? null;
        if ($request->hasFile('image_file')) {
            $imageId = StoresUploadedImages::store(
                $request->file('image_file'),
                'author_profile',
                $data['name'] ?? 'Author'
            );
        }

        $author = DB::transaction(function () use ($data, $mode, $imageId, $social) {
            if ($mode === 'existing_user') {
                $user = User::with('role')->findOrFail($data['user_id']);
                $authorRoleId = Role::where('role', 'author')->value('id');

                if ($authorRoleId && ! $user->isAuthor()) {
                    $user->update(['role_id' => $authorRoleId]);
                }

                return Author::create(array_merge([
                    'user_id' => $user->id,
                    'image_id' => $imageId,
                    'bio' => $data['bio'] ?? null,
                ], $social));
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

            return Author::create(array_merge([
                'user_id' => $user->id,
                'image_id' => $imageId,
                'bio' => $data['bio'] ?? null,
            ], $social));
        });

        $author->load(['user:id,name,email,status', 'image'])->loadCount('books');

        return response()->json([
            'message' => 'Author created successfully',
            'data' => $this->present($author),
        ], 201);
    }

    public function update(UpdateAuthorRequest $request, Author $author): JsonResponse
    {
        $data = $request->validated();
        $payload = array_merge(
            collect($data)->only(['user_id', 'image_id', 'bio'])->filter(fn ($v) => $v !== null)->all(),
            $this->socialFrom($data)
        );

        if ($request->hasFile('image_file')) {
            $payload['image_id'] = StoresUploadedImages::store(
                $request->file('image_file'),
                'author_profile',
                $author->user?->name ?? 'Author'
            );
        }

        $author->update($payload);
        $author->load(['user:id,name,email,status', 'image'])->loadCount('books');

        return response()->json([
            'message' => 'Author updated successfully',
            'data' => $this->present($author),
        ]);
    }

    public function destroy(Author $author): JsonResponse
    {
        $author->delete();

        return response()->json([
            'message' => 'Author deleted successfully',
            'data' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function socialFrom(array $data): array
    {
        return collect($data)->only([
            'website', 'facebook', 'instagram', 'twitter', 'tiktok', 'youtube', 'telegram',
        ])->all();
    }

    private function present(Author $author): array
    {
        return [
            'id' => $author->id,
            'user_id' => $author->user_id,
            'name' => $author->user?->name,
            'email' => $author->user?->email,
            'status' => $author->user?->status,
            'bio' => $author->bio,
            'website' => $author->website,
            'facebook' => $author->facebook,
            'instagram' => $author->instagram,
            'twitter' => $author->twitter,
            'tiktok' => $author->tiktok,
            'youtube' => $author->youtube,
            'telegram' => $author->telegram,
            'image_id' => $author->image_id,
            'image_url' => $author->image?->url,
            'books_count' => $author->books_count ?? $author->books()->count(),
            'created_at' => $author->created_at?->toIso8601String(),
            'updated_at' => $author->updated_at?->toIso8601String(),
        ];
    }
}
