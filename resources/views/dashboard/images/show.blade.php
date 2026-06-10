<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Image</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-[#1b1b18]">
    <div class="p-6 max-w-3xl">
        <h1 class="text-2xl font-semibold mb-4">Image #{{ $image->id }}</h1>

        <div class="space-y-3">
            <div>
                <div class="text-xs text-gray-500">Path</div>
                <div class="font-semibold">{{ $image->path }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Alt</div>
                <div class="font-semibold">{{ $image->alt }}</div>
            </div>
        </div>

        <div class="mt-6 flex gap-2">
            <a href="{{ route('dashboard.images.index') }}" class="px-3 py-2 border rounded">Back</a>
            <a href="{{ route('dashboard.images.edit', $image) }}" class="px-3 py-2 bg-black text-white rounded">Edit</a>
        </div>
    </div>
</body>

</html>