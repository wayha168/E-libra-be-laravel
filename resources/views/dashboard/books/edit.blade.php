@extends('main')

@section('title', 'Edit Book')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('dashboard.books.show', $book) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Back to book
        </a>
    </div>
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

        @php
            $currentMode = old('publish_mode', $book->isScheduled() ? 'schedule' : ($book->isDraft() ? 'draft' : 'now'));
            $scheduledValue = old('scheduled_at', optional($book->scheduled_at)->format('Y-m-d\TH:i'));
        @endphp
        <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-800 mb-1">Publish status</label>
                <p class="text-xs text-gray-500 mb-2">Current: <span class="font-medium text-gray-800">{{ $book->status ?? 'published' }}</span></p>
                <div class="flex flex-col sm:flex-row gap-3 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="publish_mode" value="now" {{ $currentMode === 'now' ? 'checked' : '' }} />
                        Publish now
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="publish_mode" value="schedule" {{ $currentMode === 'schedule' ? 'checked' : '' }} />
                        Schedule for later
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="publish_mode" value="draft" {{ $currentMode === 'draft' ? 'checked' : '' }} />
                        Save as draft
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Schedule date &amp; time</label>
                <input type="datetime-local" name="scheduled_at" value="{{ $scheduledValue }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Description</label>
            <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">{{ old('description', $book->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(!($isAuthorView ?? false))
            <div>
                <label class="block text-sm text-gray-600 mb-1">Author</label>
                <select name="author_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- Select author --</option>
                    @foreach($authors ?? [] as $author)
                    <option value="{{ $author->id }}" {{ old('author_id', $book->author_id) == $author->id ? 'selected' : '' }}>{{ $author->user->name ?? 'Author' }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="author_id" value="{{ $book->author_id }}" />
            @endif

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
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600 mb-1">Add more images</label>
                <input type="file" name="image_files[]" accept="image/*" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <p class="text-xs text-gray-500 mt-1">Select multiple files to add to this book's gallery.</p>
                @if($book->images->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($book->images as $img)
                    <img src="{{ $img->url }}" alt="{{ $img->alt_text ?? 'Book image' }}" class="h-16 w-16 object-cover rounded border border-gray-200" />
                    @endforeach
                </div>
                @elseif($book->image)
                <div class="mt-3">
                    <img src="{{ $book->image->url }}" alt="Current cover" class="h-16 w-16 object-cover rounded border border-gray-200" />
                </div>
                @endif
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">PDF File (optional)</label>
                <input type="file" name="pdf_file" accept="application/pdf" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <p class="text-xs text-gray-500 mt-1">Optional - replaces existing PDF.</p>
                @if(\App\Support\BookAccess::hasPdf($book))
                <div class="mt-2">
                    <a href="{{ route('dashboard.books.read', $book) }}" class="text-blue-600 text-xs hover:underline">Read / replace via upload above</a>
                </div>
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Price</label>
            <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $book->price) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" placeholder="0.00 or leave empty for free" />
            <p class="text-xs text-gray-500 mt-1">Leave empty or 0 if this book is free. Set a price greater than 0 if the book requires payment or subscription to access.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.books.show', $book) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</a>
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Update</button>
        </div>
    </form>
</div>
@endsection