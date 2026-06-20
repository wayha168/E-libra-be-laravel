@extends('main')

@section('title', 'Promotions')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="mt-5 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Promotions</h1>
            <p class="text-sm text-gray-600">Create and manage book discounts</p>
        </div>

        <a href="{{ route('dashboard.promotions.create') }}" class="px-4 py-2 bg-black text-white rounded-xl hover:bg-gray-800 transition">
            Add Promotion
        </a>
    </div>

    <div class="mb-3 mt-3 flex items-center justify-end">
        <x-search-filter
            :action="route('dashboard.promotions.index')"
            placeholder="Search by book title…"
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
                    <th class="text-left px-4 py-2">Book</th>
                    <th class="text-left px-4 py-2">Discount</th>
                    <th class="text-left px-4 py-2">Price</th>
                    <th class="text-left px-4 py-2">Window</th>
                    <th class="text-left px-4 py-2">Status</th>
                    <th class="text-left px-4 py-2">Created by</th>
                    <th class="text-left px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($promotions as $promotion)
                @php
                    $price = (float) ($promotion->book->price ?? 0);
                    $discounted = round($price * (1 - $promotion->discount_percent / 100), 2);
                    $live = $promotion->isCurrentlyActive();
                @endphp
                <tr class="border-t">
                    <td class="px-4 py-2 font-medium">{{ $promotion->book->title ?? '—' }}</td>
                    <td class="px-4 py-2">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-700">
                            {{ $promotion->discount_percent }}% off
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        <span class="text-gray-400 line-through">${{ number_format($price, 2) }}</span>
                        <span class="font-medium">${{ number_format($discounted, 2) }}</span>
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
                        <div class="flex items-center gap-2">
                            <a href="{{ route('dashboard.promotions.edit', $promotion) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('dashboard.promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this promotion?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </div>
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
