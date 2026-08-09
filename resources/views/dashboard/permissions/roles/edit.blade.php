@extends('main')

@section('title', $role->display_name.' Role')

@section('content')
@php
    $roleBadge = match ($role->role) {
        'super_admin' => 'bg-purple-50 text-purple-700 border-purple-200',
        'admin' => 'bg-blue-50 text-blue-700 border-blue-200',
        'author' => 'bg-amber-50 text-amber-700 border-amber-200',
        default => 'bg-gray-50 text-gray-700 border-gray-200',
    };
@endphp

<div class="max-w-5xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dashboard.permissions.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 hover:bg-gray-50 transition" title="Back to permissions">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </a>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl font-semibold">{{ $role->display_name }}</h1>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border {{ $roleBadge }}">{{ $role->role }}</span>
            </div>
            <p class="text-sm text-gray-500">View users in this role and edit its permissions</p>
        </div>
        <a href="{{ route('dashboard.permissions.create') }}" class="px-4 py-2 border border-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
            Add Permission
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Role summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Role</div>
            <div class="mt-1 text-lg font-semibold text-gray-900">{{ $role->display_name }}</div>
            <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $role->role }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Permissions</div>
            <div class="mt-1 text-2xl font-bold">{{ $role->permissions_count }}
                <span class="text-sm font-normal text-gray-400">/ {{ $allPermissions->count() }}</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Users with this role</div>
            <div class="mt-1 text-2xl font-bold">{{ $role->users_count }}</div>
        </div>
    </div>

    {{-- Users belonging to this role --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-6 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Users belonging to {{ $role->display_name }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">Accounts assigned this role inherit the permissions below</p>
        </div>

        @if($roleUsers->isEmpty())
        <div class="px-4 py-8 text-center text-gray-400 text-sm">No users currently have this role.</div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($roleUsers as $user)
            <div class="px-4 py-3 flex items-center gap-3">
                @if($user->profileImage?->url)
                <img src="{{ $user->profileImage->url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover border border-gray-200" />
                @else
                <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                </div>
                <a href="{{ route('dashboard.users.edit', $user) }}" class="text-xs text-blue-600 hover:underline shrink-0">Edit user</a>
            </div>
            @endforeach
        </div>
        @if($roleUsers->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $roleUsers->links() }}</div>
        @endif
        @endif
    </div>

    {{-- Permission ticks --}}
    <form method="POST" action="{{ route('dashboard.permissions.roles.sync', $role) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Edit permissions</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Tick to grant · untick to revoke · then save</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <input type="search" id="permFilter" placeholder="Filter permissions…" class="w-48 sm:w-56 border border-gray-300 rounded-lg px-3 py-1.5 text-xs outline-none focus:ring-2 focus:ring-black/10 focus:border-gray-400" />
                    </div>
                    <button type="button" id="selectAllPerms" class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 text-gray-700 bg-gray-50 hover:bg-gray-100 transition font-medium">Select all</button>
                    <button type="button" id="clearAllPerms" class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 text-gray-700 bg-gray-50 hover:bg-gray-100 transition font-medium">Clear all</button>
                </div>
            </div>

            @if($allPermissions->isEmpty())
            <div class="px-4 py-10 text-center text-gray-400 text-sm">
                No permissions found.
                <a href="{{ route('dashboard.permissions.create') }}" class="text-blue-600 hover:underline ml-1">Create one for a new feature</a>
            </div>
            @else
            <div class="divide-y divide-gray-100">
                @foreach($allPermissions as $permission)
                @php $hasPermission = in_array($permission->id, $assignedPermissionIds, true); @endphp
                <label class="perm-row flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50/80 transition {{ $hasPermission ? 'bg-green-50/30' : '' }}" data-search="{{ strtolower($permission->display_name.' '.$permission->name.' '.($permission->description ?? '')) }}">
                    <input
                        type="checkbox"
                        name="permissions[]"
                        value="{{ $permission->id }}"
                        class="perm-checkbox mt-1 w-4 h-4 rounded border-gray-300 text-black focus:ring-black/20"
                        @checked(in_array($permission->id, old('permissions', $assignedPermissionIds), true))
                    />
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-medium text-gray-900">{{ $permission->display_name }}</span>
                            @if($hasPermission)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-50 text-green-700">
                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Granted
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-500">
                                Revoked
                            </span>
                            @endif
                        </span>
                        <span class="block text-xs text-gray-400 font-mono mt-0.5">{{ $permission->name }}</span>
                        @if($permission->description)
                        <span class="block text-xs text-gray-500 mt-1">{{ $permission->description }}</span>
                        @endif
                    </span>
                    <a href="{{ route('dashboard.permissions.edit', $permission) }}" class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition" title="Edit permission" onclick="event.preventDefault(); event.stopPropagation(); window.location.href=this.href;">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13L2.25 21.75l.902-2.685a4.5 4.5 0 0 1 1.13-1.897Z"/></svg>
                    </a>
                </label>
                @endforeach
            </div>
            @endif

            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between gap-3 flex-wrap">
                <a href="{{ route('dashboard.permissions.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-white transition">Back</a>
                <button type="submit" class="px-5 py-2.5 bg-black text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition">
                    Save {{ $role->display_name }} permissions
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const boxes = document.querySelectorAll('.perm-checkbox');
    const rows = document.querySelectorAll('.perm-row');
    const filter = document.getElementById('permFilter');

    document.getElementById('selectAllPerms')?.addEventListener('click', function () {
        boxes.forEach(function (cb) {
            if (cb.closest('.perm-row')?.style.display !== 'none') cb.checked = true;
        });
    });
    document.getElementById('clearAllPerms')?.addEventListener('click', function () {
        boxes.forEach(function (cb) {
            if (cb.closest('.perm-row')?.style.display !== 'none') cb.checked = false;
        });
    });

    filter?.addEventListener('input', function () {
        const q = filter.value.trim().toLowerCase();
        rows.forEach(function (row) {
            const hay = row.getAttribute('data-search') || '';
            row.style.display = !q || hay.includes(q) ? '' : 'none';
        });
    });
});
</script>
@endsection
