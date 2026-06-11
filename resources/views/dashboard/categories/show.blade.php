@extends('main')

@section('title', 'Category Details')

@section('content')
<div class="max-w-2xl mx-auto">
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
@endsection