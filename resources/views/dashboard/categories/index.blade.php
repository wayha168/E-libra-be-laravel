@extends('main')

@section('title', 'Categories')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Categories</h1>
            <p class="text-sm text-gray-600">Manage your book categories</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex gap-2">
        <form method="GET" action="{{ route('dashboard.categories.index') }}" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" class="border rounded px-3 py-2" placeholder="Search" />
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Search</button>
        </form>
        <a href="{{ route('dashboard.categories.create') }}" class="ml-auto px-3 py-2 bg-black text-white rounded">Create</a>
    </div>

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">ID</th>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $category->id }}</td>
                    <td class="px-4 py-2">{{ $category->name }}</td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.categories.show', $category)"
                            :edit-url="route('dashboard.categories.edit', $category)"
                            :delete-url="route('dashboard.categories.destroy', $category)"
                            delete-confirm="Delete this category?"
                        />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
</div>
@endsection