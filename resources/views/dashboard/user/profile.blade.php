@extends('main')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <p class="text-sm text-gray-600">Welcome back</p>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
        <div id="loading" class="text-sm text-gray-600">Loading...</div>

        <div id="profile" class="hidden">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 rounded-full bg-black text-white flex items-center justify-center font-bold text-lg" id="avatarInitial"></div>
                <div>
                    <div id="name" class="text-lg font-semibold"></div>
                    <div id="email" class="text-sm text-gray-600"></div>
                    <div id="role" class="text-xs text-gray-400 mt-0.5"></div>
                </div>
            </div>

            <hr class="border-gray-100 mb-5" />

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

        <div id="error" class="hidden mt-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"></div>
    </div>
</div>
@endsection

