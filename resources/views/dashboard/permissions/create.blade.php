@extends('main')

@section('title', 'Create Permission')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dashboard.permissions.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-semibold">Create Permission</h1>
            <p class="text-sm text-gray-500">Add a new permission and assign it to roles</p>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.permissions.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Display Name</label>
                <input name="display_name" value="{{ old('display_name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-gray-400 transition" placeholder="e.g. Manage Books" required />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-gray-400 transition" rows="3" placeholder="Brief description of what this permission allows">{{ old('description') }}</textarea>
            </div>
        </div>

        {{-- Role Assignment with Checkboxes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Assign to Roles</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Select which roles should have this permission</p>
                </div>
                {{-- Quick Select Buttons --}}
                <div class="flex gap-2">
                    <button type="button" onclick="selectPreset('super_admin')" class="px-3 py-1.5 text-xs rounded-lg border border-purple-200 text-purple-700 bg-purple-50 hover:bg-purple-100 transition font-medium">Super Admin Only</button>
                    <button type="button" onclick="selectPreset('admin')" class="px-3 py-1.5 text-xs rounded-lg border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100 transition font-medium">Admin & Above</button>
                    <button type="button" onclick="selectPreset('all')" class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 text-gray-700 bg-gray-50 hover:bg-gray-100 transition font-medium">All Roles</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($roles as $role)
                <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50/50 cursor-pointer transition group" data-role="{{ $role->role }}">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="role-checkbox w-4 h-4 rounded border-gray-300 text-black focus:ring-black/20 transition" data-role-name="{{ $role->role }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }} />
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900">{{ $role->display_name }}</div>
                        <div class="text-xs text-gray-400">{{ $role->role }}</div>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium
                        @if($role->role === 'super_admin') bg-purple-50 text-purple-600
                        @elseif($role->role === 'admin') bg-blue-50 text-blue-600
                        @elseif($role->role === 'author') bg-amber-50 text-amber-600
                        @else bg-gray-100 text-gray-500
                        @endif
                    ">{{ $role->permissions->count() ?? 0 }} perms</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Existing Permissions Reference --}}
        @if($allPermissions->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">Existing Permissions</h3>
            <p class="text-xs text-gray-500 mb-3">Reference of all current permissions in the system</p>
            <div class="flex flex-wrap gap-2">
                @foreach($allPermissions as $perm)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-gray-50 border border-gray-200 text-gray-700">
                    <svg class="w-3 h-3 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    {{ $perm->display_name }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex gap-3">
            <a href="{{ route('dashboard.permissions.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Cancel</a>
            <button class="px-6 py-2.5 bg-black text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition" type="submit">Create Permission</button>
        </div>
    </form>
</div>

<script>
function selectPreset(preset) {
    const checkboxes = document.querySelectorAll('.role-checkbox');
    const hierarchyOrder = ['super_admin', 'admin', 'author', 'user'];

    checkboxes.forEach(function(cb) {
        const roleName = cb.getAttribute('data-role-name');
        if (preset === 'all') {
            cb.checked = true;
        } else if (preset === 'super_admin') {
            cb.checked = (roleName === 'super_admin');
        } else if (preset === 'admin') {
            const idx = hierarchyOrder.indexOf(roleName);
            cb.checked = (idx >= 0 && idx <= 1); // super_admin + admin
        }
    });
}
</script>
@endsection