@extends('main')

@section('title', 'Category Permissions')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Category Permissions</h1>
            <p class="text-sm text-gray-500">Assign mission permissions to multiple users for <strong>{{ $category->name }}</strong>.</p>
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

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.categories.permissions.update', $category) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-medium">Select account users</h2>
                <button type="button" id="selectAllUsers" class="text-xs text-gray-500 hover:text-black underline">Select all</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($users as $user)
                <label class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 cursor-pointer hover:border-gray-300 transition">
                    <input
                        type="checkbox"
                        name="users[]"
                        value="{{ $user->id }}"
                        class="mt-0.5 w-4 h-4 rounded border-gray-300 user-checkbox"
                        @checked(in_array($user->id, $selectedUserIds))>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-gray-900">{{ $user->name }}</span>
                        <span class="block text-xs text-gray-500 truncate">{{ $user->email }}</span>
                        <span class="inline-flex mt-1 px-1.5 py-0.5 rounded text-[10px] bg-gray-200 text-gray-600">{{ $user->display_role }}</span>
                    </span>
                </label>
                @endforeach
            </div>

            <p class="mt-3 text-xs text-gray-500">Tick one or more users, then choose the mission permission boxes below. When exactly one user is selected, their current permissions load automatically.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-medium">Mission tick boxes (category permissions)</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($permissions as $permission)
                <label class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3 cursor-pointer hover:border-gray-300 transition">
                    <input
                        type="checkbox"
                        name="permissions[]"
                        value="{{ $permission->id }}"
                        class="w-4 h-4 rounded border-gray-300 permission-checkbox">
                    <span class="text-sm font-medium text-gray-900">{{ $permission->display_name }}</span>
                </label>
                @endforeach
            </div>

            <p class="mt-3 text-xs text-gray-500">Selected permissions are applied to all checked users when you save.</p>
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
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
        const selectAllBtn = document.getElementById('selectAllUsers');

        function updatePermissions() {
            const checkedUsers = Array.from(userCheckboxes).filter(cb => cb.checked).map(cb => cb.value);

            if (checkedUsers.length === 1) {
                const userId = checkedUsers[0];
                const allowedPermissions = userPermissionsMap[userId] || [];

                permissionCheckboxes.forEach(cb => {
                    cb.checked = allowedPermissions.includes(cb.value);
                });
            } else if (checkedUsers.length !== 1) {
                permissionCheckboxes.forEach(cb => {
                    cb.checked = false;
                });
            }
        }

        userCheckboxes.forEach(cb => {
            cb.addEventListener('change', updatePermissions);
        });

        if (Array.from(userCheckboxes).filter(cb => cb.checked).length === 1) {
            updatePermissions();
        }

        selectAllBtn?.addEventListener('click', function() {
            const allChecked = Array.from(userCheckboxes).every(cb => cb.checked);
            userCheckboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
            updatePermissions();
        });
    });
</script>
@endsection
