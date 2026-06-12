@extends('main')

@section('title', 'Edit Author')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Edit Author #{{ $author->id }}</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.authors.update', $author) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm text-gray-600 mb-1">User</label>
            <select name="user_id" class="w-full border rounded px-3 py-2">
                <option value="">-- Select user --</option>
                @foreach($users ?? [] as $user)
                <option value="{{ $user->id }}" {{ old('user_id', $author->user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Bio</label>
            <textarea name="bio" class="w-full border rounded px-3 py-2" rows="3">{{ old('bio', $author->bio) }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Image</label>
                <select name="image_id" class="w-full border rounded px-3 py-2">
                    <option value="">-- No image --</option>
                    @foreach($images ?? [] as $image)
                    <option value="{{ $image->id }}" {{ old('image_id', $author->image_id) == $image->id ? 'selected' : '' }}>{{ $image->url }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.authors.show', $author) }}" class="px-3 py-2 border rounded hover:bg-gray-50 transition">Cancel</a>
            <button class="px-3 py-2 bg-black text-white rounded hover:bg-gray-800 transition" type="submit">Update</button>
        </div>
    </form>
</div>
@endsection