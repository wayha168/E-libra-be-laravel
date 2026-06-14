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

    <div class="mt-4 mb-4 flex items-center justify-end">
        <x-search-filter
            :action="route('dashboard.authors.index')"
            placeholder="Search name, email, or bio…"
        />
    </div>

    <div class="overflow-auto border rounded">
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
                @forelse($authors as $author)
                <tr class="border-t">
                    <td class="px-4 py-2"><x-short-id :value="$author->id" /></td>
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
                    <td class="px-4 py-2">{{ $author->books_count ?? 0 }}</td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.authors.show', $author)"
                            :edit-url="route('dashboard.authors.edit', $author)"
                            :delete-url="route('dashboard.authors.destroy', $author)"
                            delete-confirm="Delete this author?" />
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No authors found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $authors->links() }}</div>
</div>
@endsection
