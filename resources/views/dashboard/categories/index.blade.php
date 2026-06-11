@extends('main')

@section('title', 'Categories')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="mt-5 flex gap-2 ">
        <a href="{{ route('dashboard.categories.create') }}" class="ml-auto px-3 py-2 bg-black text-white rounded-xl hover:bg-gray-800 transition">Add Category</a>
    </div>

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Categories</h1>
            <p class="text-sm text-gray-600">Manage your book categories</p>
        </div>
        <form method="GET" action="{{ route('dashboard.categories.index') }}" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" class="border rounded px-3 py-2" placeholder="Search" />
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Search</button>
        </form>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">ID</th>
                    <th class="text-left px-4 py-2">Image</th>
                    <th class="text-left px-4 py-2">Banner Image</th>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Description</th>
                    <th class="text-left px-4 py-2">Slug</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr class="border-t">
                    <td class="px-4 py-2 max-w-xs truncate">{{ substr($category->id, 0, 3) }}...</td>
                    <td class="px-4 py-2">
                        @if($category->image?->url)
                        <img src="{{ $category->image->url }}" alt="{{ $category->image->alt_text ?? 'Image' }}" class="w-10 h-10 rounded object-cover border border-gray-200" />
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($category->bannerImage?->url)
                        <img src="{{ $category->bannerImage->url }}" alt="{{ $category->bannerImage->alt_text ?? 'Banner Image' }}" class="w-10 h-10 rounded object-cover border border-gray-200" />
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $category->name }}</td>
                    <td class="px-4 py-2">
                       <p class="line-clamp- text-gray-700">
                            {{ $category->description }}
                        </p>
                        </td>
                    <td class="px-4 py-2 truncate">
                       <span class="inline-flex px-2 py-0.5 rounded {{ $category->slug ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $category->slug ? strtolower($category->slug) : '-' }}
                    </span>
                    </td>
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