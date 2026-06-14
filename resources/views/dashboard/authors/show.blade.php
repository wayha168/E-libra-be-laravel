@extends('main')

@section('title', 'Author Details')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Author #{{ $author->id }}</h1>
        <x-table-actions
            :edit-url="route('dashboard.authors.edit', $author)"
            :delete-url="route('dashboard.authors.destroy', $author)"
            delete-confirm="Delete this author?" />
    </div>

    <div class="mt-4 space-y-3">
        <div>
            <div class="text-xs text-gray-500">Name</div>
            <div class="font-semibold">{{ $author->user->name ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Email</div>
            <div class="font-semibold">{{ $author->user->email ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Bio</div>
            <div class="whitespace-pre-wrap">{{ $author->bio ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Image</div>
            @if($author->image && $author->image->url)
            <img src="{{ $author->image->url }}" alt="Author image" class="h-16 w-16 object-cover rounded mt-1" />
            @else
            <span class="text-gray-400">-</span>
            @endif
        </div>

        <div>
            <div class="text-xs text-gray-500">Books ({{ $author->books->count() }})</div>
            @if($author->books->count() > 0)
            <ul class="mt-1 space-y-1">
                @foreach($author->books as $book)
                <li><a href="{{ route('dashboard.books.show', $book) }}" class="text-blue-600 hover:underline text-sm">{{ $book->title }}</a></li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('dashboard.authors.index') }}" class="px-3 py-2 border rounded hover:bg-gray-50 transition">Back</a>
        <a href="{{ route('dashboard.authors.books', $author) }}" class="px-3 py-2 bg-black text-white rounded hover:bg-gray-800 transition">View All Books</a>
        @if($earnings['has_author_profile'] ?? false)
        <a href="{{ route('dashboard.earnings.index') }}" class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-50 transition">View Earnings</a>
        @endif
    </div>

    @if($earnings['has_author_profile'] ?? false)
    <div class="mt-8 bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold mb-4">Sales &amp; Commission</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div class="rounded-lg bg-gray-50 p-3 text-center">
                <div class="text-xs text-gray-500">Sales</div>
                <div class="text-xl font-bold">{{ $earnings['sales_count'] }}</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3 text-center">
                <div class="text-xs text-gray-500">Gross</div>
                <div class="text-xl font-bold">${{ number_format($earnings['gross_revenue'], 2) }}</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3 text-center">
                <div class="text-xs text-gray-500">Platform {{ $earnings['platform_fee_rate'] }}%</div>
                <div class="text-xl font-bold text-purple-700">${{ number_format($earnings['platform_fee_total'], 2) }}</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3 text-center">
                <div class="text-xs text-gray-500">Net Earnings</div>
                <div class="text-xl font-bold text-green-700">${{ number_format($earnings['net_earnings'], 2) }}</div>
            </div>
        </div>
        @if(!empty($earnings['sales']))
        <div class="space-y-2">
            @foreach($earnings['sales'] as $sale)
            <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                <span>{{ $sale['book_title'] }} · {{ $sale['buyer_name'] }}</span>
                <span class="text-green-700 font-medium">${{ number_format($sale['author_earnings'], 2) }} net</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400">No paid sales yet.</p>
        @endif
    </div>
    @endif
</div>
@endsection