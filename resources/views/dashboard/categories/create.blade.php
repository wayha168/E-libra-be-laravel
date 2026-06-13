@extends('main')

@section('title', 'Create Category')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Create Category</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.categories.store') }}" class="space-y-4" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('dashboard.categories.index') }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Back</a>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Name</label>
                <input name="name" value="{{ old('name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Slug</label>
                <input name="slug" value="{{ old('slug') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Description</label>
            <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" rows="3">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Category image</label>
                <input name="image_file" type="file" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" accept="image/*" />
                <p class="text-xs text-gray-500 mt-1">Optional. Stored as an image and linked to this category.</p>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Banner image</label>
                <input name="banner_image_file" type="file" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" accept="image/*" />
                <p class="text-xs text-gray-500 mt-1">Optional. Stored as an image and linked to this category.</p>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Parent category (optional)</label>
            <input name="parent_id" value="{{ old('parent_id') }}" class="w-full md:w-1/2 border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" placeholder="UUID" />
        </div>

        <div class="flex gap-2">
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection