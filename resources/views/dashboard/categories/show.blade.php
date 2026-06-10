<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Category</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-[#1b1b18]">
    <div class="p-6 max-w-2xl">
        <h1 class="text-2xl font-semibold mb-4">Category #{{ $category->id }}</h1>

        <div class="space-y-3">
            <div>
                <div class="text-xs text-gray-500">Name</div>
                <div class="font-semibold">{{ $category->name }}</div>
            </div>
        </div>

        <div class="mt-6 flex gap-2">
            <a href="{{ route('dashboard.categories.index') }}" class="px-3 py-2 border rounded">Back</a>
            <a href="{{ route('dashboard.categories.edit', $category) }}" class="px-3 py-2 bg-black text-white rounded">Edit</a>
        </div>
    </div>
</body>

</html>