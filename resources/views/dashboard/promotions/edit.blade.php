@extends('main')

@section('title', 'Edit Promotion')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Edit Promotion</h1>

    <form method="POST" action="{{ route('dashboard.promotions.update', $promotion) }}" class="space-y-4">
        @csrf
        @method('PUT')
        @include('dashboard.promotions._form')

        <div class="flex gap-2">
            <a href="{{ route('dashboard.promotions.index') }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Back</a>
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Update</button>
        </div>
    </form>
</div>
@endsection
