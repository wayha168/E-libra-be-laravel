@extends('main')

@section('title', 'Category Details')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Category #{{ $category->id }}</h1>
            <p class="text-sm text-gray-500">View and manage category details.</p>
        </div>

        <x-table-actions
            :edit-url="route('dashboard.categories.edit', $category)"
            :delete-url="route('dashboard.categories.destroy', $category)"
            delete-confirm="Delete this category?" />

        <a href="{{ route('dashboard.categories.permissions.edit', $category) }}" class="inline-flex rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            Manage Permissions
        </a>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-medium">Images</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-medium">Category Image</h3>
                        <span class="rounded-full bg-gray-200 px-3 py-1 text-xs text-gray-600">35 x 35</span>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-100">
                        @if($category->image && $category->image->url)
                        <img
                            src="{{ $category->image->url }}"
                            alt="{{ $category->image->alt_text ?? $category->name }}"
                            class="h-[35px] w-[35px] object-cover" />
                        @else
                        <div class="flex h-[35px] w-[35px] items-center justify-center text-gray-400">
                            No image
                        </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-medium">Banner Image</h3>
                        <span class="rounded-full bg-gray-200 px-3 py-1 text-xs text-gray-600">35 x 35</span>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-100">
                        @if($category->bannerImage && $category->bannerImage->url)
                        <img
                            src="{{ $category->bannerImage->url }}"
                            alt="{{ $category->bannerImage->alt_text ?? ($category->name . ' banner') }}"
                            class="h-[35px] w-[35px] object-cover" />
                        @else
                        <div class="flex h-[35px] w-[35px] items-center justify-center text-gray-400">
                            No banner image
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-medium">Details</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Name</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $category->name ?? '-' }}</div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Slug</div>
                    <div class="mt-2">
                        <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                            {{ $category->slug ?? '-' }}
                        </span>
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Parent Category</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $category->parent_id ?? '-' }}</div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Created At</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ $category->created_at?->format('Y-m-d H:i') ?? '-' }}
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4 md:col-span-2">
                    <div class="text-xs text-gray-500">Description</div>
                    <div class="mt-1 whitespace-pre-wrap text-gray-700">
                        {{ $category->description ?? '-' }}
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4 md:col-span-2">
                    <div class="text-xs text-gray-500">Updated At</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ $category->updated_at?->format('Y-m-d H:i') ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('dashboard.categories.index') }}" class="inline-flex rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            Back to Categories
        </a>
    </div>
</div>
@endsection