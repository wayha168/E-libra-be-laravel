<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Image</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-[#1b1b18]">
    <div class="p-6 max-w-3xl">
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

        <form method="POST" action="{{ route('dashboard.images.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm text-gray-600 mb-1">Path</label>
                <input name="path" value="{{ old('path') }}" class="w-full border rounded px-3 py-2" />
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Alt</label>
                <input name="alt" value="{{ old('alt') }}" class="w-full border rounded px-3 py-2" />
            </div>

            <div class="flex gap-2">
                <a href="{{ route('dashboard.images.index') }}" class="px-3 py-2 border rounded">Back</a>
                <button class="px-3 py-2 bg-black text-white rounded" type="submit">Save</button>
            </div>
        </form>
    </div>
</body>

</html>