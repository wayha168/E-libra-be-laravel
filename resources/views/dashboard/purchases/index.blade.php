@extends('main')

@section('title', 'Book Purchases')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Book Purchases</h1>
            <p class="text-sm text-gray-600">Records of users who bought books via Stripe or direct purchase</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Total Records</div>
            <div class="mt-1 text-2xl font-bold">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Paid</div>
            <div class="mt-1 text-2xl font-bold text-green-700">{{ $stats['paid'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Pending</div>
            <div class="mt-1 text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Revenue</div>
            <div class="mt-1 text-2xl font-bold">${{ number_format($stats['revenue'], 2) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Admin Commission (10%)</div>
            <div class="mt-1 text-2xl font-bold text-purple-700">${{ number_format($stats['admin_commission'] ?? 0, 2) }}</div>
        </div>
    </div>

    <div class="mb-4 flex gap-2 flex-wrap">
        <x-search-filter
            :action="route('dashboard.purchases.index')"
            placeholder="Search user or book…"
            submit-label="Filter"
            :filters="[[
                'name' => 'status',
                'options' => [
                    '' => 'All statuses',
                    'paid' => 'Paid',
                    'pending' => 'Pending',
                    'canceled' => 'Canceled',
                ],
            ]]"
        />
    </div>

    <div class="overflow-auto border border-gray-200 rounded-xl bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">User</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">Book</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">Amount</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">Payment</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">Admin 10%</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">Author Net</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">Purchased At</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase">Stripe Session</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($purchases as $purchase)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $purchase->user?->name ?? '-' }}</div>
                        <div class="text-xs text-gray-500">{{ $purchase->user?->email ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $purchase->book?->title ?? '-' }}</div>
                        <div class="text-xs text-gray-400 font-mono">{{ Str::limit($purchase->book_id, 12) }}</div>
                    </td>
                    <td class="px-4 py-3 font-semibold">${{ number_format($purchase->amount ?? 0, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ ($purchase->payment_method ?? 'card') === 'khqr' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $purchase->paymentMethodLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-purple-700 font-medium">
                        @if($purchase->status === 'paid' && $purchase->admin_commission_amount)
                        ${{ number_format($purchase->admin_commission_amount, 2) }}
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-green-700 font-medium">
                        @if($purchase->status === 'paid')
                        ${{ number_format($purchase->authorEarnings(), 2) }}
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            @if($purchase->status === 'paid') bg-green-50 text-green-700
                            @elseif($purchase->status === 'pending') bg-amber-50 text-amber-700
                            @else bg-gray-100 text-gray-600
                            @endif
                        ">{{ ucfirst($purchase->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $purchase->purchased_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td class="px-4 py-3 text-xs font-mono text-gray-400">{{ $purchase->stripe_checkout_session_id ? Str::limit($purchase->stripe_checkout_session_id, 20) : '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-400">No purchase records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $purchases->links() }}</div>
</div>
@endsection
