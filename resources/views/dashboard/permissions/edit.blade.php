@extends('main')

@section('title', 'Update Permission')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Update Permission</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.permissions.update', $permission) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm text-gray-600 mb-1">Display Name</label>
            <input name="display_name" value="{{ old('display_name', $permission->display_name) }}" class="w-full border rounded px-3 py-2" required />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Description</label>
            <textarea name="description" class="w-full border rounded px-3 py-2" rows="3">{{ old('description', $permission->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Roles</label>
            <div class="flex flex-wrap gap-2">
                @foreach($roles as $role)
                <label class="flex items-center gap-1 text-sm">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded" {{ in_array($role->id, $assignedRoles) ? 'checked' : '' }} />
                    {{ $role->display_name }}
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.permissions.index') }}" class="px-3 py-2 border rounded">Back</a>
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection