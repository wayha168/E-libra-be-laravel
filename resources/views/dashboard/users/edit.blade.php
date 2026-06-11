@extends('main')

@section('title', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Edit User #{{ $user->id }}</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.users.update', $user) }}" class="space-y-4" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if($user->profileImage?->url)
        <div>
            <label class="block text-sm text-gray-600 mb-1">Current Photo</label>
            <img src="{{ $user->profileImage->url }}" alt="{{ $user->profileImage->alt_text ?? $user->name }}" class="w-20 h-20 rounded-full object-cover border border-gray-200" />
        </div>
        @endif

        <div>
            <label class="block text-sm text-gray-600 mb-1">Name</label>
            <input name="name" value="{{ old('name', $user->name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Email</label>
            <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">New Password (optional)</label>
                <input name="password" type="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Confirm New Password</label>
                <input name="password_confirmation" type="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Role</label>
                <select name="role_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ (string) old('role_id', $user->role_id) === (string) $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Status</label>
                <select name="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="active" {{ old('status', $user->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Upload New Photo</label>
            <input type="file" name="image_file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.users.show', $user) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</a>
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Update</button>
        </div>
    </form>
</div>
@endsection
