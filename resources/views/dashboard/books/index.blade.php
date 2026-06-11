@extends('main')

@section('title', 'Books')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Books</h1>
            <p class="text-sm text-gray-600">Manage your book library</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex gap-2">
        <form method="GET" action="{{ route('dashboard.books.index') }}" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" class="border rounded px-3 py-2" placeholder="Search" />
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Search</button>
        </form>
        <a href="{{ route('dashboard.books.create') }}" class="ml-auto px-3 py-2 bg-black text-white rounded">Create</a>
    </div>

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">ID</th>
                    <th class="text-left px-4 py-2">Title</th>
                    <th class="text-left px-4 py-2">Description</th>
                    <th class="text-left px-4 py-2">Author ID</th>
                    <th class="text-left px-4 py-2">Category ID</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($books as $book)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $book->id }}</td>
                    <td class="px-4 py-2">{{ $book->title }}</td>
                    <td class="px-4 py-2">{{ Str::limit($book->description ?? '', 60) }}</td>
                    <td class="px-4 py-2">{{ $book->author_id }}</td>
                    <td class="px-4 py-2">{{ $book->category_id }}</td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.books.show', $book)"
                            :edit-url="route('dashboard.books.edit', $book)"
                            :delete-url="route('dashboard.books.destroy', $book)"
                            delete-confirm="Delete this book?"
                        />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $books->links() }}</div>
</div>
@endsection