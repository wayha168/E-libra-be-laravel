<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Api\DashboardOverviewController;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\AuthorEarnings;
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

        $users = $query->latest()->paginate(10)->withQueryString();

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

        DashboardOverviewController::broadcastStats();

        return redirect()->route('dashboard.users.index')->with('success', 'User created successfully');
    }

    public function show(User $user): View
    {
        $user->load(['role.permissions', 'profileImage', 'authorProfile.books.category']);

        $purchases = $user->bookPurchases()
            ->with('book:id,title,price,author_id')
            ->latest()
            ->get();

        $comments = $user->bookComments()
            ->with('book:id,title')
            ->latest()
            ->take(10)
            ->get();

        $authorEarnings = AuthorEarnings::forUser($user);

        $authorSales = collect($authorEarnings['sales'] ?? []);

        $activityStats = [
            'subscription' => (bool) $user->user_subscribe,
            'books_purchased' => $purchases->where('status', 'paid')->count(),
            'total_spent' => (float) $purchases->where('status', 'paid')->sum('amount'),
            'pending_orders' => $purchases->where('status', 'pending')->count(),
            'books_authored' => $user->authorProfile?->books()->count() ?? 0,
            'author_sales' => $authorEarnings['sales_count'],
            'gross_revenue' => $authorEarnings['gross_revenue'],
            'platform_fee_total' => $authorEarnings['platform_fee_total'],
            'author_earnings' => $authorEarnings['net_earnings'],
            'comments_count' => $user->bookComments()->count(),
        ];

        return view('dashboard.users.show', compact(
            'user',
            'purchases',
            'comments',
            'authorSales',
            'authorEarnings',
            'activityStats',
        ));
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
            'payway_account' => $data['payway_account'] ?? null,
            'bakong_account' => $data['bakong_account'] ?? null,
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
