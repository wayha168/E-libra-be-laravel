@extends('main')

@section('title', 'Create Category')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Create Category</h1>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.categories.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm text-gray-600 mb-1">Name</label>
            <input name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2" />
        </div>

        <div class="flex gap-2">
            <a href="{{ route('dashboard.categories.index') }}" class="px-3 py-2 border rounded">Back</a>
            <button class="px-3 py-2 bg-black text-white rounded" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection