@extends('main')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Dashboard Overview</h1>

    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Summary</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">Books</div>
                    <div id="bookCount" class="mt-1 text-2xl font-semibold">-</div>
                </div>
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">Categories</div>
                    <div id="categoryCount" class="mt-1 text-2xl font-semibold">-</div>
                </div>
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">Images</div>
                    <div id="imageCount" class="mt-1 text-2xl font-semibold">-</div>
                </div>
            </div>
</div>
@endsection
