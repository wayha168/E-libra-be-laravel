@extends('main')

@section('title', 'Edit Image')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Edit image</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.images.update', $image) }}" class="space-y-4" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if($image->url)
        <div>
            <label class="block text-sm text-gray-600 mb-1">Current image</label>
            <img src="{{ $image->url }}" alt="{{ $image->alt_text ?? 'Image' }}" class="w-24 h-24 rounded object-cover border border-gray-200" />
        </div>
        @endif

        <div>
            <label class="block text-sm text-gray-600 mb-1">Upload new image</label>
            <input type="file" name="image_file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Url</label>
            <input name="url" value="{{ old('url', $image->url) }}" class="w-full border rounded px-3 py-2" />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Alt text</label>
            <input name="alt_text" value="{{ old('alt_text', $image->alt_text) }}" class="w-full border rounded px-3 py-2" />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Type</label>
            <input name="image_type" value="{{ old('image_type', $image->image_type) }}" class="w-full border rounded px-3 py-2" />
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.images.show', $image) }}" class="px-3 py-2 border rounded">Cancel</a>
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Update</button>
        </div>
    </form>
</div>
@endsection
