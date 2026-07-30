@extends('main')

@section('title', 'Promotions')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="mt-5 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Promotions</h1>
            <p class="text-sm text-gray-600">Percentage discounts or free trials for a book or all of an author’s books</p>
        </div>

        <a href="{{ route('dashboard.promotions.create') }}" class="px-4 py-2 bg-black text-white rounded-xl hover:bg-gray-800 transition">
            Add Promotion
        </a>
    </div>

    <div class="mb-3 mt-3 flex items-center justify-end">
        <x-search-filter
            :action="route('dashboard.promotions.index')"
            placeholder="Search book or author…"
        />
    </div>

    @if(session('success'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="overflow-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">Target</th>
                    <th class="text-left px-4 py-2">Type</th>
                    <th class="text-left px-4 py-2">Offer</th>
                    <th class="text-left px-4 py-2">Window</th>
                    <th class="text-left px-4 py-2">Status</th>
                    <th class="text-left px-4 py-2">Created by</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($promotions as $promotion)
                @php
                    $live = $promotion->isCurrentlyActive();
                    $isTrial = $promotion->isFreeTrial();
                    $isAuthorScope = $promotion->author_id && ! $promotion->book_id;
                    $target = $isAuthorScope
                        ? ('All books · ' . ($promotion->author->user->name ?? 'Author'))
                        : ($promotion->book->title ?? '—');
                @endphp
                <tr class="border-t">
                    <td class="px-4 py-2 font-medium">
                        {{ $target }}
                        <div class="text-xs text-gray-500">{{ $isAuthorScope ? 'Author scope' : 'Book scope' }}</div>
                    </td>
                    <td class="px-4 py-2">
                        @if($isTrial)
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-sky-50 text-sky-700">Free trial</span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-700">Discount</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($isTrial)
                            {{ $promotion->resolvedTrialDays() }} days free
                        @else
                            {{ $promotion->discount_percent }}% off
                            @if($promotion->book)
                            @php
                                $price = (float) ($promotion->book->price ?? 0);
                                $discounted = round($price * (1 - $promotion->discount_percent / 100), 2);
                            @endphp
                            <div class="text-xs text-gray-500">
                                <span class="line-through">${{ number_format($price, 2) }}</span>
                                ${{ number_format($discounted, 2) }}
                            </div>
                            @endif
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-600">
                        {{ $promotion->starts_at?->format('Y-m-d H:i') ?? 'Now' }}
                        &rarr;
                        {{ $promotion->ends_at?->format('Y-m-d H:i') ?? 'No end' }}
                    </td>
                    <td class="px-4 py-2">
                        @if($live)
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-green-50 text-green-700">Live</span>
                        @elseif($promotion->is_active)
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">Scheduled/Expired</span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-600">{{ $promotion->creator->name ?? '—' }}</td>
                    <td class="px-4 py-2">
                        <x-table-actions
                            :edit-url="route('dashboard.promotions.edit', $promotion)"
                            :delete-url="route('dashboard.promotions.destroy', $promotion)"
                            delete-confirm="Delete this promotion?" />
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No promotions yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $promotions->links() }}</div>
</div>
@endsection
