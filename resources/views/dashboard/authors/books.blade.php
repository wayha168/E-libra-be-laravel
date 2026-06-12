@extends('main')

@section('title', 'Books by Author')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Books by {{ $author->user->name ?? 'Author' }}</h1>
            <div class="text-gray-600">{{ $author->bio ?? '' }}</div>
        </div>
        <a href="{{ route('dashboard.authors.index') }}" class="px-3 py-2 border rounded hover:bg-gray-50 transition">Back</a>
    </div>

    @if(!isset($books) || $books->count() === 0)
    <div class="mt-4 rounded border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800 text-sm">No books found for this author.</div>
    @else
    <div class="mt-4 overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">Title</th>
                    <th class="text-left px-4 py-2">Category</th>
                    <th class="text-left px-4 py-2">Public date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($books as $book)
                <tr class="border-t">
                    <td class="px-4 py-2">
                        <a href="{{ route('dashboard.books.show', $book) }}" class="font-semibold hover:underline">{{ $book->title }}</a>
                    </td>
                    <td class="px-4 py-2">
                        {{ $book->category->name ?? '-' }}
                    </td>
                    <td class="px-4 py-2">
                        {{ $book->public_date?->format('Y-m-d') ?? '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $books->links() }}</div>
    @endif
</div>
@endsection