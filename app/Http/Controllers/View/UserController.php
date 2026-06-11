<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\StoresUploadedImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController
{
    public function index(Request $request): View
    {
        $query = User::query()->with(['role', 'profileImage']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        $users = $query->latest()->paginate(10);

        return view('dashboard.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('role')->get();

        return view('dashboard.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $imageId = StoresUploadedImages::store(
            $request->file('image_file'),
            'profile',
            $data['name']
        );

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'confirm_password' => $data['password'],
            'role_id' => $data['role_id'],
            'status' => $data['status'],
            'profile_image_id' => $imageId,
        ]);

        return redirect()->route('dashboard.users.index')->with('success', 'User created successfully');
    }

    public function show(User $user): View
    {
        $user->load(['role', 'profileImage']);

        return view('dashboard.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $roles = Role::orderBy('role')->get();
        $user->load('profileImage');

        return view('dashboard.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'status' => $data['status'],
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
            $payload['confirm_password'] = $data['password'];
        }

        if ($request->hasFile('image_file')) {
            StoresUploadedImages::deleteById($user->profile_image_id);
            $payload['profile_image_id'] = StoresUploadedImages::store(
                $request->file('image_file'),
                'profile',
                $data['name']
            );
        }

        $user->update($payload);

        return redirect()->route('dashboard.users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('dashboard.users.index')->with('error', 'You cannot delete your own account.');
        }

        StoresUploadedImages::deleteById($user->profile_image_id);
        $user->delete();

        return redirect()->route('dashboard.users.index')->with('success', 'User deleted successfully');
    }
}
