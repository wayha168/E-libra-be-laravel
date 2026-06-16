@extends('main')

@section('title', 'Books')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-4 flex gap-2 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">{{ ($isAuthorView ?? false) ? 'My Books' : 'Books' }}</h1>
            <p class="text-sm text-gray-600">{{ ($isAuthorView ?? false) ? 'Books you have published' : 'Manage your book library' }}</p>
        </div>
        <a href="{{ route('dashboard.books.create') }}" class="px-2 py-2 bg-black text-white rounded-xl hover:bg-gray-800 transition">Add Book</a>
    </div>
    <div class="mb-3 mt-3 flex items-center justify-end">
        <x-search-filter
            :action="route('dashboard.books.index')"
            placeholder="Search title, author, or category…"
        />
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">ID</th>
                    <th class="text-left px-4 py-2">Title</th>
                    <th class="text-left px-4 py-2">Description</th>
                    @if(!($isAuthorView ?? false))
                    <th class="text-left px-4 py-2">Author</th>
                    @endif
                    <th class="text-left px-4 py-2">Category</th>
                    <th class="text-left px-4 py-2">Price</th>
                    <th class="text-left px-4 py-2">Likes</th>
                    <th class="text-left px-4 py-2">Comments</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                <tr class="border-t">
                    <td class="px-4 py-2"><x-short-id :value="$book->id" /></td>
                    <td class="px-4 py-2 font-medium">{{ $book->title }}</td>
                    <td class="px-4 py-2">{{ Str::limit($book->description ?? '', 60) }}</td>
                    @if(!($isAuthorView ?? false))
                    <td class="px-4 py-2">{{ $book->author?->user?->name ?? '—' }}</td>
                    @endif
                    <td class="px-4 py-2">{{ $book->category?->name ?? '—' }}</td>
                    <td class="px-4 py-2">${{ number_format((float) ($book->price ?? 0), 2) }}</td>
                    <td class="px-4 py-2">{{ $book->likes_count ?? 0 }}</td>
                    <td class="px-4 py-2">{{ $book->comments_count ?? 0 }}</td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.books.show', $book)"
                            :edit-url="route('dashboard.books.edit', $book)"
                            :delete-url="route('dashboard.books.destroy', $book)"
                            delete-confirm="Delete this book?" />
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ ($isAuthorView ?? false) ? 8 : 9 }}" class="px-4 py-6 text-center text-gray-400">No books found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $books->links() }}</div>
</div>
@endsection
