@extends('main')

@section('title', 'Image Details')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Image details</h1>

    <div class="space-y-4">
        @if($image->url)
        <div>
            <div class="text-xs text-gray-500 mb-2">Preview</div>
            <img src="{{ $image->url }}" alt="{{ $image->alt_text ?? 'Image' }}" class="w-32 h-32 rounded object-cover border border-gray-200" />
        </div>
        @endif

        <div>
            <div class="text-xs text-gray-500">Url</div>
            <div class="font-semibold break-all">{{ $image->url }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Alt text</div>
            <div class="font-semibold">{{ $image->alt_text ?? '-' }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Type</div>
            <div class="font-semibold">{{ $image->image_type ?? '-' }}</div>
        </div>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('dashboard.images.index') }}" class="px-3 py-2 border rounded">Back</a>
        <a href="{{ route('dashboard.images.edit', $image) }}" class="px-3 py-2 bg-black text-white rounded">Edit</a>
    </div>
</div>
@endsection
