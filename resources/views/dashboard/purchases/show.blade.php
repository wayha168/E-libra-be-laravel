@extends('main')

@section('title', 'Order Details')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-4">
        <a href="{{ (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) ? route('dashboard.purchases.index') : route('dashboard.profile') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Back to purchases
        </a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Order Details</h1>
            <p class="text-xs text-gray-400 font-mono mt-1">ID: {{ $purchase->id }}</p>
        </div>
        <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium
            @if($purchase->status === 'paid') bg-green-50 text-green-700
            @elseif($purchase->status === 'pending') bg-amber-50 text-amber-700
            @else bg-gray-100 text-gray-600
            @endif
        ">{{ ucfirst($purchase->status) }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Payment</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Amount</span>
                    <span class="font-semibold">${{ number_format($purchase->amount ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Payment method</span>
                    <span>{{ $purchase->paymentMethodLabel() }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Admin commission</span>
                    <span class="text-purple-700 font-medium">
                        @if($purchase->status === 'paid' && $purchase->admin_commission_amount)
                        ${{ number_format($purchase->admin_commission_amount, 2) }}
                        @else
                        —
                        @endif
                    </span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Author net</span>
                    <span class="text-green-700 font-medium">
                        @if($purchase->status === 'paid')
                        ${{ number_format($purchase->authorEarnings(), 2) }}
                        @else
                        —
                        @endif
                    </span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Purchased at</span>
                    <span>{{ $purchase->purchased_at?->format('Y-m-d H:i') ?? '—' }}</span>
                </div>
                @if($purchase->stripe_checkout_session_id)
                <div>
                    <div class="text-gray-500 mb-1">Stripe session</div>
                    <div class="font-mono text-xs text-gray-600 break-all">{{ $purchase->stripe_checkout_session_id }}</div>
                </div>
                @endif
                @if($purchase->stripe_payment_intent_id)
                <div>
                    <div class="text-gray-500 mb-1">Stripe payment intent</div>
                    <div class="font-mono text-xs text-gray-600 break-all">{{ $purchase->stripe_payment_intent_id }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Book</h2>
                    @if($purchase->book)
                    <a href="{{ route('dashboard.books.show', $purchase->book) }}" class="text-xs text-blue-600 hover:underline">View book →</a>
                    @endif
                </div>
                @if($purchase->book)
                <div class="space-y-2 text-sm">
                    <div class="font-medium text-gray-900">{{ $purchase->book->title }}</div>
                    <div class="text-gray-500">{{ $purchase->book->category?->name ?? '—' }}</div>
                    <div class="text-gray-500">Author: {{ $purchase->book->author?->user?->name ?? '—' }}</div>
                    <div class="text-gray-500">List price: ${{ number_format((float) ($purchase->book->price ?? 0), 2) }}</div>
                </div>
                @else
                <p class="text-sm text-gray-400">Book not found.</p>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Buyer</h2>
                    @if($purchase->user && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
                    <a href="{{ route('dashboard.users.show', $purchase->user) }}" class="text-xs text-blue-600 hover:underline">View user →</a>
                    @endif
                </div>
                @if($purchase->user)
                <div class="space-y-1 text-sm">
                    <div class="font-medium text-gray-900">{{ $purchase->user->name }}</div>
                    <div class="text-gray-500">{{ $purchase->user->email }}</div>
                </div>
                @else
                <p class="text-sm text-gray-400">User not found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
