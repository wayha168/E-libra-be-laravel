@extends('main')

@section('title', 'User Details')

@section('content')
@php $canViewPresence = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(); @endphp
<div class="max-w-6xl mx-auto" id="userShowPage" data-can-view-presence="{{ $canViewPresence ? '1' : '0' }}">
    <div class="flex items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">User Details</h1>
            <p class="text-sm text-gray-500">Profile, subscription, purchases, and author activity</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.users.edit', $user) }}" class="px-4 py-2 bg-black text-white rounded-lg text-sm hover:bg-gray-800 transition">Edit</a>
            <a href="{{ route('dashboard.users.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition">Back</a>
        </div>
    </div>

    {{-- Profile header --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="h-20 bg-gradient-to-r from-gray-900 via-gray-800 to-gray-700"></div>
        <div class="px-6 pb-6">
            <div class="flex items-end gap-4 -mt-10">
                @if($user->profileImage?->url)
                <img src="{{ $user->profileImage->url }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-xl object-cover border-4 border-white shadow-lg" />
                @else
                <div class="w-20 h-20 rounded-xl bg-black text-white flex items-center justify-center text-2xl font-bold border-4 border-white shadow-lg">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                @endif
                <div class="flex-1 min-w-0 pb-1">
                    <h2 class="text-xl font-bold text-gray-900 truncate">{{ $user->name }}</h2>
                    <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                </div>
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold
                    @if($user->role?->role === 'super_admin') bg-purple-50 text-purple-700
                    @elseif($user->role?->role === 'admin') bg-blue-50 text-blue-700
                    @elseif($user->role?->role === 'author') bg-amber-50 text-amber-700
                    @else bg-gray-100 text-gray-600
                    @endif
                ">{{ $user->display_role }}</span>
                @if($canViewPresence)
                <span data-presence-user="{{ $user->id }}">
                    @if($user->isOnline())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Online</span>
                    @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Offline</span>
                    @endif
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Activity stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-xs text-gray-500">Subscription</div>
            <div class="mt-1 text-sm font-bold {{ $activityStats['subscription'] ? 'text-green-700' : 'text-gray-400' }}">
                {{ $activityStats['subscription'] ? 'Active' : 'None' }}
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-xs text-gray-500">Books Bought</div>
            <div class="mt-1 text-xl font-bold">{{ $activityStats['books_purchased'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-xs text-gray-500">Total Spent</div>
            <div class="mt-1 text-xl font-bold">${{ number_format($activityStats['total_spent'], 2) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-xs text-gray-500">Pending Orders</div>
            <div class="mt-1 text-xl font-bold text-amber-600">{{ $activityStats['pending_orders'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-xs text-gray-500">Books Authored</div>
            <div class="mt-1 text-xl font-bold">{{ $activityStats['books_authored'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-xs text-gray-500">Comments</div>
            <div class="mt-1 text-xl font-bold">{{ $activityStats['comments_count'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Account info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Account Information</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Status</span>
                    <span class="font-medium">{{ $user->display_status }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">User ID</span>
                    <span class="font-mono text-xs text-gray-600"><x-short-id :value="$user->id" /></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Registered</span>
                    <span class="font-medium">{{ $user->created_at?->format('M d, Y H:i') ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Last Updated</span>
                    <span class="font-medium">{{ $user->updated_at?->format('M d, Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Payout accounts (PayWay / Bakong) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">Payout Accounts</h3>
            <p class="text-xs text-gray-500 mb-4">Manage bank, PayWay, and Bakong accounts for payouts</p>
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <a href="{{ route('dashboard.account.bank.index', ['user_id' => $user->id]) }}" class="inline-block mb-4 text-sm text-blue-600 hover:underline">Manage bank details →</a>
            @endif
            <div class="space-y-3 text-sm">
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">PayWay Account</div>
                    <div class="mt-1 font-medium text-gray-900">{{ $user->payway_account ?: '—' }}</div>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Bakong Account</div>
                    <div class="mt-1 font-medium text-gray-900">{{ $user->bakong_account ?: '—' }}</div>
                </div>
            </div>
            @if($user->authorProfile)
            <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                <div>
                    <div class="text-xs text-gray-500">Author Sales</div>
                    <div class="font-bold text-lg">{{ $activityStats['author_sales'] }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Gross Revenue</div>
                    <div class="font-bold text-lg">${{ number_format($activityStats['gross_revenue'] ?? 0, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Platform Fee</div>
                    <div class="font-bold text-lg text-purple-700">${{ number_format($activityStats['platform_fee_total'] ?? 0, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Author Earnings</div>
                    <div class="font-bold text-lg text-green-700">${{ number_format($activityStats['author_earnings'], 2) }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Permissions --}}
    @if($user->role?->permissions?->count())
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Role Permissions</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($user->role->permissions as $perm)
            <span class="inline-flex px-2 py-1 rounded-lg text-xs bg-green-50 text-green-700 border border-green-100">{{ $perm->display_name }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Book purchases --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Book Purchase Activity</h3>
        @if($purchases->isEmpty())
        <p class="text-sm text-gray-400">No book purchases yet.</p>
        @else
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-3 py-2 text-xs text-gray-500">Book</th>
                        <th class="text-left px-3 py-2 text-xs text-gray-500">Amount</th>
                        <th class="text-left px-3 py-2 text-xs text-gray-500">Admin 10%</th>
                        <th class="text-left px-3 py-2 text-xs text-gray-500">Status</th>
                        <th class="text-left px-3 py-2 text-xs text-gray-500">Date</th>
                        <th class="text-left px-3 py-2 text-xs text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($purchases as $purchase)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $purchase->book?->title ?? '—' }}</td>
                        <td class="px-3 py-2">${{ number_format($purchase->amount ?? 0, 2) }}</td>
                        <td class="px-3 py-2 text-purple-700">
                            @if($purchase->status === 'paid' && $purchase->admin_commission_amount)
                            ${{ number_format($purchase->admin_commission_amount, 2) }}
                            @else
                            —
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                @if($purchase->status === 'paid') bg-green-50 text-green-700
                                @elseif($purchase->status === 'pending') bg-amber-50 text-amber-700
                                @else bg-gray-100 text-gray-600
                                @endif
                            ">{{ ucfirst($purchase->status) }}</span>
                        </td>
                        <td class="px-3 py-2 text-gray-500">{{ $purchase->purchased_at?->format('M d, Y H:i') ?? '—' }}</td>
                        <td class="px-3 py-2">
                            <x-table-actions :view-url="route('dashboard.purchases.show', $purchase)" />
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Author sales (when user owns books) --}}
    @if(!empty($authorSales) && count($authorSales) > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Sales on Authored Books</h3>
            @if(auth()->user()->isAuthor() && auth()->id() === $user->id)
            <a href="{{ route('dashboard.earnings.index') }}" class="text-xs text-blue-600 hover:underline">View full earnings</a>
            @endif
        </div>
        <div class="space-y-2">
            @foreach($authorSales as $sale)
            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 text-sm">
                <div>
                    <div class="font-medium">{{ $sale['book_title'] ?? 'Book' }}</div>
                    <div class="text-xs text-gray-500">Buyer: {{ $sale['buyer_name'] ?? '—' }} ({{ $sale['buyer_email'] ?? '—' }})</div>
                </div>
                <div class="text-right">
                    <div class="font-semibold">${{ number_format($sale['amount'] ?? 0, 2) }}</div>
                    <div class="text-xs text-purple-600">Platform: ${{ number_format($sale['platform_fee'] ?? 0, 2) }}</div>
                    <div class="text-xs text-green-700 font-medium">You: ${{ number_format($sale['author_earnings'] ?? 0, 2) }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @elseif($user->authorProfile)
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-2">Sales on Authored Books</h3>
        <p class="text-sm text-gray-400">No paid sales yet.</p>
        @if(auth()->user()->isAuthor() && auth()->id() === $user->id)
        <a href="{{ route('dashboard.earnings.index') }}" class="mt-2 inline-block text-sm text-blue-600 hover:underline">My earnings dashboard</a>
        @endif
    </div>
    @endif

    {{-- Comments activity --}}
    @if($comments->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Recent Book Comments</h3>
        <div class="space-y-2">
            @foreach($comments as $comment)
            <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm">
                <div class="text-xs text-gray-500">On <strong>{{ $comment->book?->title ?? 'Book' }}</strong> · {{ $comment->created_at?->format('M d, Y H:i') }}</div>
                <div class="mt-1 text-gray-800">{{ $comment->body }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
