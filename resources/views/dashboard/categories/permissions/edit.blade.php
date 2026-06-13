@extends('main')

@section('title', 'Category Permissions')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Category Permissions</h1>
            <p class="text-sm text-gray-500">Manage category access permissions for multiple users.</p>
        </div>

        <a href="{{ route('dashboard.categories.show', $category) }}" class="inline-flex rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            Back
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.categories.permissions.update', $category) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-medium">Select users</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($users as $user)
                <label class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3 cursor-pointer">
                    <input
                        type="checkbox"
                        name="users[]"
                        value="{{ $user->id }}"
                        class="w-4 h-4 rounded border-gray-300">
                    <span class="text-sm font-medium text-gray-900">{{ $user->name }}</span>
                </label>
                @endforeach
            </div>

            <p class="mt-3 text-xs text-gray-500">Tip: tick users you want to apply the selected permissions to.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-medium">Mission tick boxes (category permissions)</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($permissions as $permission)
                @php
                $isCheckedForAll = false;
                @endphp
                <label class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3 cursor-pointer">
                    <input
                        type="checkbox"
                        name="permissions[]"
                        value="{{ $permission->id }}"
                        class="w-4 h-4 rounded border-gray-300">
                    <span class="text-sm font-medium text-gray-900">{{ $permission->display_name }}</span>
                </label>
                @endforeach
            </div>

            <p class="mt-3 text-xs text-gray-500">These permission checkboxes are applied to the selected users.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.categories.show', $category) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
                Save permissions
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userPermissionsMap = @json($selectedPermissionIdsByUser);
        const userCheckboxes = document.querySelectorAll('input[name="users[]"]');
        const permissionCheckboxes = document.querySelectorAll('input[name="permissions[]"]');

        function updatePermissions() {
            const checkedUsers = Array.from(userCheckboxes).filter(cb => cb.checked).map(cb => cb.value);

            if (checkedUsers.length === 1) {
                const userId = checkedUsers[0];
                const allowedPermissions = userPermissionsMap[userId] || [];

                permissionCheckboxes.forEach(cb => {
                    cb.checked = allowedPermissions.includes(cb.value);
                });
            } else {
                permissionCheckboxes.forEach(cb => {
                    cb.checked = false;
                });
            }
        }

        userCheckboxes.forEach(cb => {
            cb.addEventListener('change', updatePermissions);
        });
    });
</script>
@endsection