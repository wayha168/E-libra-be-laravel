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

    <div class="mt-4 mb-4 flex items-center justify-end">
        <x-search-filter
            :action="route('dashboard.authors.books', $author)"
            placeholder="Search title or description…"
        />
    </div>

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">Title</th>
                    <th class="text-left px-4 py-2">Category</th>
                    <th class="text-left px-4 py-2">Public date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
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
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-8 text-center text-gray-400">No books found for this author.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $books->links() }}</div>
</div>
@endsection
