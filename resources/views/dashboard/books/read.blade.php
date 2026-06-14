@extends('main')

@section('title', 'Read — ' . $book->title)

@section('content')
<div id="bookReadPage" class="max-w-5xl mx-auto" data-book-id="{{ $book->id }}">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <a href="{{ route('dashboard.books.show', $book) }}" class="text-xs text-gray-500 hover:text-gray-800">&larr; Back to book</a>
            <h1 class="text-xl font-semibold mt-1">{{ $book->title }}</h1>
            <p id="readAccessHint" class="text-sm text-gray-500 mt-1">
                @if($book->has_full_access)
                    Full access — you can read the entire book.
                @elseif($book->can_preview)
                    Free preview — first {{ $book->trial_pages }} pages. Subscribe or buy for full access.
                @else
                    Loading access…
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(!$book->has_full_access && ($book->price ?? 0) > 0)
            <a href="{{ route('dashboard.profile') }}" class="px-3 py-1.5 bg-black text-white text-xs font-medium rounded-lg hover:bg-gray-800">Subscribe / Buy</a>
            @endif
            @if($book->has_full_access)
            <a id="downloadPdfBtn" href="{{ route('dashboard.books.pdf', $book) }}" class="px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-lg hover:bg-gray-50">Download PDF</a>
            @endif
        </div>
    </div>

    <div id="readError" class="hidden mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"></div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div id="pdfToolbar" class="flex items-center justify-between gap-3 px-4 py-2 border-b border-gray-100 bg-gray-50 text-sm">
            <button type="button" id="pdfPrevBtn" class="px-2 py-1 rounded hover:bg-gray-200 disabled:opacity-40" disabled>&larr; Prev</button>
            <span id="pdfPageInfo">Page 1</span>
            <button type="button" id="pdfNextBtn" class="px-2 py-1 rounded hover:bg-gray-200 disabled:opacity-40" disabled>Next &rarr;</button>
        </div>
        <div id="pdfCanvasWrap" class="min-h-[70vh] flex items-center justify-center bg-gray-100 p-4">
            <canvas id="pdfCanvas" class="max-w-full shadow-lg bg-white"></canvas>
        </div>
    </div>
</div>
@endsection
