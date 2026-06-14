@extends('main')

@section('title', 'Permissions')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Permissions</h1>
            <p class="text-sm text-gray-600">Manage role permissions and user access</p>
        </div>
        <a href="{{ route('dashboard.permissions.create') }}" class="px-4 py-2 bg-black text-white rounded-xl hover:bg-gray-800 transition inline-flex items-center gap-2">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Permission
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Permission Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Total Permissions</div>
            <div class="mt-1 text-2xl font-bold">{{ $permissions->total() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Total Roles</div>
            <div class="mt-1 text-2xl font-bold">{{ $roles->count() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Total Users</div>
            <div class="mt-1 text-2xl font-bold">{{ $users->total() }}</div>
        </div>
    </div>

    {{-- Permissions Table --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">All Permissions</h2>
            <x-search-filter
                :action="route('dashboard.permissions.index')"
                placeholder="Search permissions…"
                :preserve="['user_search' => request('user_search')]"
            />
        </div>

        <div class="overflow-auto border border-gray-200 rounded-xl bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Permission</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Description</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Assigned Roles</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($permissions as $permission)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $permission->display_name }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $permission->name }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $permission->description ?: '-' }}</td>
                        <td class="px-4 py-3">
                            @forelse($permission->roles as $role)
                            <span class="inline-flex px-2 py-0.5 mr-1 mb-1 rounded-full text-xs font-medium
                                @if($role->role === 'super_admin') bg-purple-50 text-purple-700
                                @elseif($role->role === 'admin') bg-blue-50 text-blue-700
                                @else bg-gray-100 text-gray-600
                                @endif
                            ">{{ $role->display_name }}</span>
                            @empty
                            <span class="text-gray-400 text-xs">No roles</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3">
                            <x-table-actions
                                :view-url="route('dashboard.permissions.show', $permission)"
                                :edit-url="route('dashboard.permissions.edit', $permission)"
                                :delete-url="route('dashboard.permissions.destroy', $permission)"
                                delete-confirm="Delete this permission?"
                                class="justify-center"
                            />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                            No permissions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $permissions->links() }}</div>
    </div>

    {{-- Users & Their Permissions --}}
    <div>
        <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
            <h2 class="text-lg font-semibold">User Permission Overview</h2>
            <x-search-filter
                :action="route('dashboard.permissions.index')"
                search-name="user_search"
                placeholder="Search users by name or email…"
                :preserve="['search' => request('search')]"
            />
        </div>
        <div class="overflow-auto border border-gray-200 rounded-xl bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">User</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Email</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Role</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide"># Permissions</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Permissions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                    @php
                        $userPermissions = $user->role ? $user->role->permissions : collect();
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($user->profileImage?->url)
                                <img src="{{ $user->profileImage->url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover border border-gray-200" />
                                @else
                                <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                @endif
                                <span class="font-medium text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                @if($user->role?->role === 'super_admin') bg-purple-50 text-purple-700
                                @elseif($user->role?->role === 'admin') bg-blue-50 text-blue-700
                                @elseif($user->role?->role === 'author') bg-amber-50 text-amber-700
                                @else bg-gray-100 text-gray-600
                                @endif
                            ">{{ $user->display_role }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($user->isSuperAdmin())
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700">All</span>
                            @else
                            <span class="font-semibold">{{ $userPermissions->count() }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($user->isSuperAdmin())
                            <span class="text-xs text-purple-600 italic">Full access (Super Admin)</span>
                            @elseif($userPermissions->count() > 0)
                            <div class="flex flex-wrap gap-1">
                                @foreach($userPermissions->take(4) as $perm)
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[11px] bg-green-50 text-green-700">{{ $perm->display_name }}</span>
                                @endforeach
                                @if($userPermissions->count() > 4)
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[11px] bg-gray-100 text-gray-500">+{{ $userPermissions->count() - 4 }} more</span>
                                @endif
                            </div>
                            @else
                            <span class="text-gray-400 text-xs">None</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</div>
@endsection