@extends('main')

@section('title', 'Edit Book')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Edit Book #{{ $book->id }}</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.books.update', $book) }}" class="space-y-4" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Title</label>
                <input name="title" value="{{ old('title', $book->title) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Public Date</label>
                <input type="date" name="public_date" value="{{ old('public_date', optional($book->public_date)->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Description</label>
            <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">{{ old('description', $book->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Author</label>
                <select name="author_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- Select author --</option>
                    @foreach($authors ?? [] as $author)
                    <option value="{{ $author->id }}" {{ old('author_id', $book->author_id) == $author->id ? 'selected' : '' }}>{{ $author->user->name ?? 'Author' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Category</label>
                <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- Select category --</option>
                    @foreach($categories ?? [] as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $book->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Cover Image</label>
                <input type="file" name="image_file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image.</p>
                @if($book->image)
                <div class="mt-2">
                    <img src="{{ $book->image->url }}" alt="Current cover" class="h-16 w-16 object-cover rounded" />
                </div>
                @endif
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">PDF File (optional)</label>
                <input type="file" name="pdf_file" accept="application/pdf" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <p class="text-xs text-gray-500 mt-1">Optional - replaces existing PDF.</p>
                @if($book->pdf_file)
                <div class="mt-2">
                    <a href="{{ $book->pdf_file }}" target="_blank" class="text-blue-600 text-xs hover:underline">Current PDF</a>
                </div>
                @endif
            </div>
        </div>

        <div class="border rounded-lg p-4 bg-gray-50">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_subscribed" value="1" {{ old('is_subscribed', $book->is_subscribed) ? 'checked' : '' }} class="w-4 h-4 border border-gray-300 rounded" />
                <span class="text-sm text-gray-700">Requires subscription/payment to access</span>
            </label>
            <p class="text-xs text-gray-500 mt-1">When checked, users must subscribe/pay to unlock this book.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.books.show', $book) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</a>
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Update</button>
        </div>
    </form>
</div>
@endsection