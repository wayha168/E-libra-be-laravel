<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Book</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-[#1b1b18]">
    <div class="p-6 max-w-3xl">
        <h1 class="text-2xl font-semibold mb-4">Create Book</h1>

        @if($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('dashboard.books.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm text-gray-600 mb-1">Title</label>
                <input name="title" value="{{ old('title') }}" class="w-full border rounded px-3 py-2" />
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Description</label>
                <textarea name="description" class="w-full border rounded px-3 py-2">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Author ID</label>
                    <input name="author_id" value="{{ old('author_id') }}" class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Category ID</label>
                    <input name="category_id" value="{{ old('category_id') }}" class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Image ID</label>
                    <input name="image_id" value="{{ old('image_id') }}" class="w-full border rounded px-3 py-2" />
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('dashboard.books.index') }}" class="px-3 py-2 border rounded">Back</a>
                <button class="px-3 py-2 bg-black text-white rounded" type="submit">Save</button>
            </div>
        </form>
    </div>
</body>

</html>