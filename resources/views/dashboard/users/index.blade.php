@extends('main')

@section('title', 'Users')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Users</h1>
            <p class="text-sm text-gray-600">Manage platform users</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-4 flex gap-2">
        <form method="GET" action="{{ route('dashboard.users.index') }}" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" class="border rounded px-3 py-2" placeholder="Search name or email" />
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Search</button>
        </form>
        <a href="{{ route('dashboard.users.create') }}" class="ml-auto px-3 py-2 bg-black text-white rounded">Create</a>
    </div>

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">Photo</th>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Email</th>
                    <th class="text-left px-4 py-2">Role</th>
                    <th class="text-left px-4 py-2">Status</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="border-t">
                    <td class="px-4 py-2">
                        @if($user->profileImage?->url)
                        <img src="{{ $user->profileImage->url }}" alt="{{ $user->profileImage->alt_text ?? $user->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200" />
                        @else
                        <div class="w-10 h-10 rounded-full bg-black/5 flex items-center justify-center text-xs font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $user->name }}</td>
                    <td class="px-4 py-2">{{ $user->email }}</td>
                    <td class="px-4 py-2">{{ $user->display_role }}</td>
                    <td class="px-4 py-2">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs {{ $user->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $user->display_status }}
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.users.show', $user)"
                            :edit-url="route('dashboard.users.edit', $user)"
                            :delete-url="route('dashboard.users.destroy', $user)"
                            delete-confirm="Delete this user?"
                        />
                    </td>
                </tr>
                @empty
                <tr class="border-t">
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
