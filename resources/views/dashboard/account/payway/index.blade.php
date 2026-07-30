@extends('main')

@section('title', 'ABA PayWay Merchants')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">ABA PayWay</h1>
            <p class="text-sm text-gray-500">Merchant credentials for each author or admin (super admin only)</p>
        </div>
        <a href="{{ route('dashboard.account.payway.create') }}" class="px-4 py-2 bg-black text-white rounded-xl text-sm hover:bg-gray-800">Add credentials</a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-3">Owner</th>
                    <th class="text-left px-4 py-3">Merchant ID</th>
                    <th class="text-left px-4 py-3">Environment</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($merchants as $merchant)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $merchant->user?->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $merchant->user?->email }} · {{ $merchant->user?->display_role }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-mono text-xs">{{ $merchant->merchant_id }}</div>
                        <div class="text-xs text-gray-500">{{ $merchant->merchant_name ?: '—' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div>{{ ucfirst($merchant->environment) }}</div>
                        <div class="text-xs text-gray-500">{{ $merchant->currency }} · {{ $merchant->paymentOptionLabel() }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @if($merchant->is_active)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700">Active</span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-table-actions
                            :edit-url="route('dashboard.account.payway.edit', $merchant)"
                            :delete-url="route('dashboard.account.payway.destroy', $merchant)"
                            delete-confirm="Delete ABA PayWay credentials for this user?" />
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">No ABA PayWay merchants yet. Add credentials for an author or admin.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
