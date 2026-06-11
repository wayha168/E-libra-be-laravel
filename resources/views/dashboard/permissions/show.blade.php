@extends('main')

@section('title', 'Permission Details')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Permission #{{ substr($permission->id, 0, 3) }}...</h1>

    <div class="space-y-3">
        <div>
            <div class="text-xs text-gray-500">Display Name</div>
            <div class="font-semibold">{{ $permission->display_name }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Description</div>
            <div>{{ $permission->description ?: '-' }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Roles</div>
            <div class="flex flex-wrap gap-1 mt-1">
                @forelse($permission->roles as $role)
                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700">{{ $role->display_name }}</span>
                @empty
                <span class="text-gray-500">-</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('dashboard.permissions.index') }}" class="px-3 py-2 border rounded">Back</a>
        <a href="{{ route('dashboard.permissions.edit', $permission) }}" class="px-3 py-2 bg-black text-white rounded">Edit</a>
    </div>
</div>
@endsection