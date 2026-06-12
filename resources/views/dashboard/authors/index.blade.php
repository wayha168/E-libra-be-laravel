@extends('main')

@section('title', 'Authors')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Authors</h1>
            <p class="text-sm text-gray-600">Manage your authors</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mt-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    @if(!isset($authors) || $authors->count() === 0)
    <div class="mt-4 rounded border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800 text-sm">No authors found.</div>
    @else
    <div class="mt-4 overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">ID</th>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Bio</th>
                    <th class="text-left px-4 py-2">Image</th>
                    <th class="text-left px-4 py-2">Books</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($authors as $author)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $author->id }}</td>
                    <td class="px-4 py-2">
                        <div class="font-semibold">{{ $author->user->name ?? 'Author' }}</div>
                        @if($author->user && $author->user->email)
                        <span class="text-gray-500 text-xs">{{ $author->user->email }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-wrap" style="max-width: 300px;">
                        {{ Str::limit($author->bio ?? '-', 60) }}
                    </td>
                    <td class="px-4 py-2">
                        @if($author->image && $author->image->url)
                        <img src="{{ $author->image->url }}" alt="Author image" class="h-10 w-10 object-cover rounded" />
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $author->books_count ?? $author->books->count() }}</td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.authors.show', $author)"
                            :edit-url="route('dashboard.authors.edit', $author)"
                            :delete-url="route('dashboard.authors.destroy', $author)"
                            delete-confirm="Delete this author?" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $authors->links() }}</div>
    @endif
</div>
@endsection