@extends('main')

@section('title', 'Create Book')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Create Book</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.books.store') }}" class="space-y-4" enctype="multipart/form-data">
        @csrf

        <div>
            <label class="block text-sm text-gray-600 mb-1">Title</label>
            <input name="title" value="{{ old('title') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Description</label>
            <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Category</label>
            <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                <option value="">-- Select category --</option>
                @foreach($categories ?? [] as $category)
                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Upload Image</label>
            <input type="file" name="image_file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            <input type="hidden" name="image_id" value="{{ old('image_id') }}" />
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.books.index') }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Back</a>
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection