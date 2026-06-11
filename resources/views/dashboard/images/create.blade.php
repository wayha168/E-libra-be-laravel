@extends('main')

@section('title', 'Create Image')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Create Image</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.images.store') }}" class="space-y-4" enctype="multipart/form-data">
        @csrf

        <div>
            <label class="block text-sm text-gray-600 mb-1">Upload image</label>
            <input type="file" name="image_file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Url (optional if uploading file)</label>
            <input name="url" value="{{ old('url') }}" class="w-full border rounded px-3 py-2" />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Alt text</label>
            <input name="alt_text" value="{{ old('alt_text') }}" class="w-full border rounded px-3 py-2" />
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Type</label>
            <input name="image_type" value="{{ old('image_type') }}" class="w-full border rounded px-3 py-2" placeholder="general, profile, book..." />
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.images.index') }}" class="px-3 py-2 border rounded">Back</a>
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection
