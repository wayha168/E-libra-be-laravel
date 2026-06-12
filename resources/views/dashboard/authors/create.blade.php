@extends('main')

@section('title', 'Create Author')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Create Author</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.authors.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">User</label>
                <select name="user_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- Select user --</option>
                    @foreach($users ?? [] as $user)
                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Image (optional)</label>
                <select name="image_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- No image --</option>
                    @foreach($images ?? [] as $image)
                    <option value="{{ $image->id }}" {{ old('image_id') == $image->id ? 'selected' : '' }}>{{ Str::limit($image->url, 30) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Bio</label>
            <textarea name="bio" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" rows="3">{{ old('bio') }}</textarea>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.authors.index') }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Back</a>
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection