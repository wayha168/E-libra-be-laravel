<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Images Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-[#1b1b18]">
    <div class="p-6">
        <h1 class="text-2xl font-semibold mb-4">Images</h1>

        @if(session('success'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
        @endif

        <div class="mb-4 flex gap-2">
            <form method="GET" action="{{ route('dashboard.images.index') }}" class="flex gap-2">
                <input name="search" value="{{ request('search') }}" class="border rounded px-3 py-2" placeholder="Search" />
                <button class="px-3 py-2 bg-black text-white rounded">Search</button>
            </form>
            <a href="{{ route('dashboard.images.create') }}" class="ml-auto px-3 py-2 bg-black text-white rounded">Create</a>
        </div>

        <div class="overflow-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2">ID</th>
                        <th class="text-left px-4 py-2">Path</th>
                        <th class="text-left px-4 py-2">Alt</th>
                        <th class="text-left px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($images as $image)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $image->id }}</td>
                        <td class="px-4 py-2">{{ $image->path }}</td>
                        <td class="px-4 py-2">{{ $image->alt }}</td>
                        <td class="px-4 py-2 flex gap-2">
                            <a class="underline" href="{{ route('dashboard.images.show', $image) }}">View</a>
                            <a class="underline" href="{{ route('dashboard.images.edit', $image) }}">Edit</a>
                            <form method="POST" action="{{ route('dashboard.images.destroy', $image) }}" onsubmit="return confirm('Delete this image?')">
                                @csrf
                                @method('DELETE')
                                <button class="underline text-red-600" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $images->links() }}
        </div>
    </div>
</body>

</html>