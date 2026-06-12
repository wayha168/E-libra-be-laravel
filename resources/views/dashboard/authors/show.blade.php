@extends('main')

@section('title', 'Author Details')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Author #{{ $author->id }}</h1>
        <x-table-actions
            :edit-url="route('dashboard.authors.edit', $author)"
            :delete-url="route('dashboard.authors.destroy', $author)"
            delete-confirm="Delete this author?" />
    </div>

    <div class="mt-4 space-y-3">
        <div>
            <div class="text-xs text-gray-500">Name</div>
            <div class="font-semibold">{{ $author->user->name ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Email</div>
            <div class="font-semibold">{{ $author->user->email ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Bio</div>
            <div class="whitespace-pre-wrap">{{ $author->bio ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Image</div>
            @if($author->image && $author->image->url)
            <img src="{{ $author->image->url }}" alt="Author image" class="h-16 w-16 object-cover rounded mt-1" />
            @else
            <span class="text-gray-400">-</span>
            @endif
        </div>

        <div>
            <div class="text-xs text-gray-500">Books ({{ $author->books->count() }})</div>
            @if($author->books->count() > 0)
            <ul class="mt-1 space-y-1">
                @foreach($author->books as $book)
                <li><a href="{{ route('dashboard.books.show', $book) }}" class="text-blue-600 hover:underline text-sm">{{ $book->title }}</a></li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('dashboard.authors.index') }}" class="px-3 py-2 border rounded hover:bg-gray-50 transition">Back</a>
        <a href="{{ route('dashboard.authors.books', $author) }}" class="px-3 py-2 bg-black text-white rounded hover:bg-gray-800 transition">View All Books</a>
    </div>
</div>
@endsection