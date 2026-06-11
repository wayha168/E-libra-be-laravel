@extends('main')

@section('title', 'Permissions')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-4 mt-5 flex gap-2">
        <a href="{{ route('dashboard.permissions.create') }}" class="ml-auto px-3 py-2 bg-black text-white rounded-xl hover:bg-gray-800 transition">Add Permission</a>
    </div>

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Permissions</h1>
            <p class="text-sm text-gray-600">Manage role permissions</p>
        </div>
        <form method="GET" action="{{ route('dashboard.permissions.index') }}" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" class="border rounded px-3 py-2" placeholder="Search" />
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Search</button>
        </form>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">ID</th>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Description</th>
                    <th class="text-left px-4 py-2">Roles</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $permission)
                <tr class="border-t">
                    <td class="px-4 py-2 max-w-xs truncate">{{ substr($permission->id, 0, 3) }}...</td>
                    <td class="px-4 py-2">{{ $permission->display_name }}</td>
                    <td class="px-4 py-2">{{ $permission->description ?: '-' }}</td>
                    <td class="px-4 py-2">
                        @foreach($permission->roles as $role)
                        <span class="inline-flex px-2 py-0.5 mr-1 mb-1 rounded text-xs bg-blue-50 text-blue-700">{{ $role->display_name }}</span>
                        @endforeach
                    </td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.permissions.show', $permission)"
                            :edit-url="route('dashboard.permissions.edit', $permission)"
                            :delete-url="route('dashboard.permissions.destroy', $permission)"
                            delete-confirm="Delete this permission?"
                        />
                    </td>
                </tr>
                @empty
                <tr class="border-t">
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No permissions found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $permissions->links() }}</div>
</div>
@endsection