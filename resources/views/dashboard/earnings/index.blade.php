@extends('main')

@section('title', 'My Earnings')

@section('content')
<div id="authorEarningsPage" class="max-w-6xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">My Earnings</h1>
        <p class="text-sm text-gray-500">Sales on your books — platform fee {{ $earnings['platform_fee_rate'] }}%, your net payout</p>
    </div>

    @if(!$earnings['has_author_profile'])
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        No author profile is linked to your account yet. Ask an admin to link your user to an author profile.
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-xs text-gray-500 uppercase">Total Sales</div>
            <div class="mt-1 text-2xl font-bold">{{ $earnings['sales_count'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-xs text-gray-500 uppercase">Gross Revenue</div>
            <div class="mt-1 text-2xl font-bold">${{ number_format($earnings['gross_revenue'], 2) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-xs text-gray-500 uppercase">Platform Fee ({{ $earnings['platform_fee_rate'] }}%)</div>
            <div class="mt-1 text-2xl font-bold text-purple-700">${{ number_format($earnings['platform_fee_total'], 2) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-xs text-gray-500 uppercase">Your Net Earnings</div>
            <div class="mt-1 text-2xl font-bold text-green-700">${{ number_format($earnings['net_earnings'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Income Over Time</h2>
            <select id="authorChartPeriod" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
                <option value="6m" selected>Last 6 months</option>
                <option value="12m">Last 12 months</option>
            </select>
        </div>
        <div class="h-72">
            <canvas id="authorIncomeChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Sales &amp; Commission Breakdown</h2>
            @if(empty($earnings['sales']))
            <p class="text-sm text-gray-400">No paid sales on your books yet.</p>
            @else
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-3 py-2 text-xs text-gray-500">Book</th>
                            <th class="text-left px-3 py-2 text-xs text-gray-500">Buyer</th>
                            <th class="text-left px-3 py-2 text-xs text-gray-500">Sale</th>
                            <th class="text-left px-3 py-2 text-xs text-gray-500">Payment</th>
                            <th class="text-left px-3 py-2 text-xs text-gray-500">Platform {{ $earnings['platform_fee_rate'] }}%</th>
                            <th class="text-left px-3 py-2 text-xs text-gray-500">You Receive</th>
                            <th class="text-left px-3 py-2 text-xs text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($earnings['sales'] as $sale)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $sale['book_title'] ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <div>{{ $sale['buyer_name'] ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $sale['buyer_email'] ?? '' }}</div>
                            </td>
                            <td class="px-3 py-2">${{ number_format($sale['amount'], 2) }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ ($sale['payment_method'] ?? 'card') === 'khqr' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $sale['payment_method_label'] ?? 'Card' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-purple-700">${{ number_format($sale['platform_fee'], 2) }}</td>
                            <td class="px-3 py-2 font-semibold text-green-700">${{ number_format($sale['author_earnings'], 2) }}</td>
                            <td class="px-3 py-2 text-gray-500 text-xs">{{ $sale['purchased_at'] ? \Carbon\Carbon::parse($sale['purchased_at'])->format('M d, Y H:i') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Payout Accounts</h2>
            <p class="text-xs text-gray-500 mb-4">Configure PayWay or Bakong to receive your earnings.</p>
            <div class="space-y-3 text-sm">
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <div class="text-xs text-gray-500 uppercase">PayWay</div>
                    <div class="mt-1 font-medium">{{ $earnings['payway_account'] ?: '— not set —' }}</div>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <div class="text-xs text-gray-500 uppercase">Bakong</div>
                    <div class="mt-1 font-medium">{{ $earnings['bakong_account'] ?: '— not set —' }}</div>
                </div>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <a href="{{ route('dashboard.users.edit', $user) }}" class="mt-4 inline-block text-sm text-blue-600 hover:underline">Edit payout accounts</a>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
