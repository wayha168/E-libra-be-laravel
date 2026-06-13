@extends('main')

@section('title', 'Book Details')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Book #{{ $book->id }}</h1>
        <x-table-actions
            :edit-url="route('dashboard.books.edit', $book)"
            :delete-url="route('dashboard.books.destroy', $book)"
            delete-confirm="Delete this book?" />
    </div>

    <div class="mt-4 space-y-3">
        <div>
            <div class="text-xs text-gray-500">Title</div>
            <div class="font-semibold">{{ $book->title }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Description</div>
            <div class="whitespace-pre-wrap">{{ $book->description ?? '-' }}</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-500">Author</div>
                <div class="font-semibold">{{ $book->author->user->name ?? '-' }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-500">Category</div>
                <div class="font-semibold">{{ $book->category->name ?? '-' }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-500">Public Date</div>
                <div class="font-semibold">{{ $book->public_date?->format('Y-m-d') ?? '-' }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-500">Price / Access</div>
                <span class="inline-flex px-2 py-0.5 rounded text-xs {{ ($book->price > 0) ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }}">
                    {{ ($book->price > 0) ? '$' . number_format($book->price, 2) : 'Free' }}
                </span>
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Cover Image</div>
            @if($book->image)
            <img src="{{ $book->image->url }}" alt="Book cover" class="h-20 w-20 object-cover rounded mt-1" />
            @else
            <span class="text-gray-400">-</span>
            @endif
        </div>

        @if($book->pdf_file)
        <div>
            <div class="text-xs text-gray-500">PDF File</div>
            <a href="{{ $book->pdf_file }}" target="_blank" class="text-blue-600 text-sm hover:underline">Download PDF</a>
        </div>
        @endif
    </div>

    <div class="mt-6">
        <a href="{{ route('dashboard.books.index') }}" class="px-3 py-2 border rounded hover:bg-gray-50 transition">Back</a>
    </div>
</div>
@endsection