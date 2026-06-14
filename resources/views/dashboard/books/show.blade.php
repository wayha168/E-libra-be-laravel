@extends('main')

@section('title', $book->title)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('dashboard.books.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Back to books
        </a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $book->title }}</h1>
            <p class="text-xs text-gray-400 font-mono mt-1">ID: {{ $book->id }}</p>
        </div>
        <x-table-actions
            :edit-url="route('dashboard.books.edit', $book)"
            :delete-url="route('dashboard.books.destroy', $book)"
            delete-confirm="Delete this book?" />
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex flex-col gap-6">
            @php $gallery = $book->galleryImages(); @endphp
            @if($gallery->isNotEmpty())
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">Images</div>
                <div class="flex flex-wrap gap-3">
                    @foreach($gallery as $img)
                    <img src="{{ $img->url }}" alt="{{ $img->alt_text ?? $book->title }}" class="w-24 h-32 object-cover rounded-lg border border-gray-100 shadow-sm" />
                    @endforeach
                </div>
            </div>
            @endif

            <div class="space-y-4">
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Description</div>
                    <div class="mt-1 text-sm text-gray-800 whitespace-pre-wrap">{{ $book->description ?? '—' }}</div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Author</div>
                        <div class="text-sm font-medium">{{ $book->author->user->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Category</div>
                        <div class="text-sm font-medium">{{ $book->category->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Price</div>
                        <span class="inline-flex mt-0.5 px-2 py-0.5 rounded text-xs font-medium {{ ($book->price > 0) ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }}">
                            {{ ($book->price > 0) ? '$' . number_format($book->price, 2) : 'Free' }}
                        </span>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Published</div>
                        <div class="text-sm font-medium">{{ $book->public_date?->format('Y-m-d') ?? '—' }}</div>
                    </div>
                </div>
                @if($hasPdf ?? false)
                <div>
                    <div class="text-xs text-gray-500 mb-1">PDF (secured)</div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('dashboard.books.read', $book) }}" class="inline-flex px-3 py-1.5 bg-black text-white text-xs font-medium rounded-lg hover:bg-gray-800">Read online</a>
                        <a href="{{ route('dashboard.books.pdf', $book) }}" class="inline-flex px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-lg hover:bg-gray-50">Open full PDF</a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div id="bookFeedbackRoot" data-book-id="{{ $book->id }}" data-mode="management" class="space-y-6">
        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                <div class="text-xs text-gray-500 uppercase">Likes</div>
                <div id="bookLikeCount" class="text-2xl font-bold mt-1">{{ $book->likes_count ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                <div class="text-xs text-gray-500 uppercase">Comments</div>
                <div id="bookCommentCount" class="text-2xl font-bold mt-1">{{ $book->comments_count ?? 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">Users who liked this book</h2>
            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase">Liked at</th>
                        </tr>
                    </thead>
                    <tbody id="bookLikesList" class="divide-y divide-gray-100">
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <button type="button" id="bookLikesMore" class="hidden mt-3 text-xs text-blue-600 hover:underline">Load more</button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">Comments</h2>
            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase">Comment</th>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase">Posted at</th>
                        </tr>
                    </thead>
                    <tbody id="bookCommentsList" class="divide-y divide-gray-100">
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <button type="button" id="bookCommentsMore" class="hidden mt-3 text-xs text-blue-600 hover:underline">Load more</button>
        </div>
    </div>
</div>
@endsection
