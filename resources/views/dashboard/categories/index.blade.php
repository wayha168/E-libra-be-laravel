@extends('main')

@section('title', 'Categories')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="mt-5 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Categories</h1>
            <p class="text-sm text-gray-600">Manage your book categories</p>
        </div>

        <a href="{{ route('dashboard.categories.create') }}" class="px-4 py-2 bg-black text-white rounded-xl hover:bg-gray-800 transition">
            Add Category
        </a>
    </div>


    <div class="mb-3 mt-3 flex items-center justify-end">
        <form method="GET" action="{{ route('dashboard.categories.index') }}" class="flex gap-2 w-full max-w-md">
            <input name="search" value="{{ request('search') }}" class="w-full border rounded px-3 py-2" placeholder="Search" />
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Search</button>
        </form>
    </div>


    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">ID</th>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Description</th>
                    <th class="text-left px-4 py-2">Slug</th>
                    <th class="text-left px-4 py-2">Image</th>
                    <th class="text-left px-4 py-2">Banner</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr class="border-t">
                    <td class="px-4 py-2 max-w-xs truncate">{{ $category->id }}</td>


                    <td class="px-4 py-2">{{ $category->name }}</td>

                    <td class="px-4 py-2">
                        @if($category->image)
                        <img
                            src="{{ $category->image->url }}"
                            alt="{{ $category->image->alt_text ?? $category->name }}"
                            class="h-10 w-10 object-cover rounded" />
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>

                    <td class="px-4 py-2">
                        @if($category->bannerImage)
                        <img
                            src="{{ $category->bannerImage->url }}"
                            alt="{{ $category->bannerImage->alt_text ?? ($category->name . ' banner') }}"
                            class="h-10 w-10 object-cover rounded" />
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>

                    <td class="px-4 py-2">
                        <p class="line-clamp-2 text-gray-700">{{ $category->description ?? '-' }}</p>
                    </td>

                    <td class="px-4 py-2 truncate">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700">
                            {{ $category->slug ? strtolower($category->slug) : '-' }}
                        </span>
                    </td>

                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.categories.show', $category)"
                            :edit-url="route('dashboard.categories.edit', $category)"
                            :delete-url="route('dashboard.categories.destroy', $category)"
                            delete-confirm="Delete this category?" />
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
</div>
@endsection