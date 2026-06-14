@extends('main')

@section('title', 'Bank Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Bank Details</h1>
            <p class="text-sm text-gray-500">
                @if($owner->id !== auth()->id())
                Payout accounts for {{ $owner->name }}
                @else
                Manage your bank, PayWay, and Bakong accounts for payouts
                @endif
            </p>
        </div>
        <a href="{{ route('dashboard.account.bank.create', request('user_id') ? ['user_id' => request('user_id')] : []) }}" class="px-4 py-2 bg-black text-white rounded-xl text-sm hover:bg-gray-800">Add Account</a>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-3">Provider</th>
                    <th class="text-left px-4 py-3">Account holder</th>
                    <th class="text-left px-4 py-3">Account number</th>
                    <th class="text-left px-4 py-3">Default</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($accounts as $account)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $account->providerLabel() }}</div>
                        <div class="text-xs text-gray-500">{{ $account->bank_name ?: '—' }}</div>
                    </td>
                    <td class="px-4 py-3">{{ $account->account_holder }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $account->account_number }}</td>
                    <td class="px-4 py-3">
                        @if($account->is_default)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700">Default</span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-table-actions
                            :edit-url="route('dashboard.account.bank.edit', array_filter(['bank' => $account, 'user_id' => request('user_id')]))"
                            :delete-url="route('dashboard.account.bank.destroy', array_filter(['bank' => $account, 'user_id' => request('user_id')]))"
                            delete-confirm="Delete this bank account?" />
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">No bank accounts yet. Add one to receive payouts.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
