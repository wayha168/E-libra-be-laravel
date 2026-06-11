@extends('main')

@section('title', 'Book Details')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Book #{{ $book->id }}</h1>

    <div class="space-y-3">
        <div>
            <div class="text-xs text-gray-500">Title</div>
            <div class="font-semibold">{{ $book->title }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Description</div>
            <div class="whitespace-pre-wrap">{{ $book->description }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Author ID</div>
            <div class="font-semibold">{{ $book->author_id }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Category ID</div>
            <div class="font-semibold">{{ $book->category_id }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Image ID</div>
            <div class="font-semibold">{{ $book->image_id }}</div>
        </div>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('dashboard.books.index') }}" class="px-3 py-2 border rounded">Back</a>
        <a href="{{ route('dashboard.books.edit', $book) }}" class="px-3 py-2 bg-black text-white rounded">Edit</a>
    </div>
</div>
@endsection