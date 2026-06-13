@extends('main')

@section('title', 'Images')

@section('content')
<div class="max-w-5xl mx-auto">
    
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Images</h1>
            <p class="text-sm text-gray-600">Manage your image library</p>
        </div>
        <a href="{{ route('dashboard.images.create') }}" class="ml-auto px-3 py-2 bg-black text-white rounded-xl justify-end transition hover:bg-gray-800">Add Image</a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex gap-2 items-center justify-end">
        <form method="GET" action="{{ route('dashboard.images.index') }}" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" class="border rounded px-3 py-2" placeholder="Search" />
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Search</button>
        </form>
    </div>

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">Preview</th>
                    <th class="text-left px-4 py-2">Url</th>
                    <th class="text-left px-4 py-2">Alt text</th>
                    <th class="text-left px-4 py-2">Type</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($images as $image)
                <tr class="border-t">
                    <td class="px-4 py-2">
                        @if($image->url)
                        <img src="{{ $image->url }}" alt="{{ $image->alt_text ?? 'Image' }}" class="w-10 h-10 rounded object-cover border border-gray-200" />
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-4 py-2 max-w-xs truncate">{{ $image->url }}</td>
                    <td class="px-4 py-2">{{ $image->alt_text ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $image->image_type ?? '-' }}</td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :view-url="route('dashboard.images.show', $image)"
                            :edit-url="route('dashboard.images.edit', $image)"
                            :delete-url="route('dashboard.images.destroy', $image)"
                            delete-confirm="Delete this image?"
                        />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $images->links() }}</div>
</div>
@endsection
