@extends('main')

@section('title', 'Create Book')

@section('content')
<div class="max-w-4xl mx-auto">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Title</label>
                <input name="title" value="{{ old('title') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Public Date</label>
                <input type="date" name="public_date" value="{{ old('public_date') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Description</label>
            <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" rows="3">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(!($isAuthorView ?? false))
            <div>
                <label class="block text-sm text-gray-600 mb-1">Author</label>
                <select name="author_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- Select author --</option>
                    @foreach($authors ?? [] as $author)
                    <option value="{{ $author->id }}" {{ old('author_id') == $author->id ? 'selected' : '' }}>{{ $author->user->name ?? 'Author' }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="author_id" value="{{ $user->authorProfile?->id }}" />
            @endif

            <div>
                <label class="block text-sm text-gray-600 mb-1">Category</label>
                <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- Select category --</option>
                    @foreach($categories ?? [] as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600 mb-1">Book Images</label>
                <input type="file" name="image_files[]" accept="image/*" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <p class="text-xs text-gray-500 mt-1">Upload one or more images for this book. The first image is used as the cover.</p>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">PDF File (optional)</label>
                <input type="file" name="pdf_file" accept="application/pdf" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <p class="text-xs text-gray-500 mt-1">Optional book PDF for download.</p>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Price</label>
            <input type="number" name="price" step="0.01" min="0" value="{{ old('price') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" placeholder="0.00 or leave empty for free" />
            <p class="text-xs text-gray-500 mt-1">Leave empty or 0 if this book is free. Set a price greater than 0 if the book requires payment or subscription to access.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.books.index') }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Back</a>
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection