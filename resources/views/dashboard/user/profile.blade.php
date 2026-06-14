@extends('main')

@section('title', 'Profile')

@section('content')
<div id="userProfilePage" class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Profile</h1>
        <p class="text-sm text-gray-500">Your account details and permissions</p>
    </div>

    <div id="paymentBanner" class="hidden mb-4 rounded-xl border px-4 py-3 text-sm"></div>

    <div id="loading" class="flex items-center justify-center py-20">
        <div class="flex flex-col items-center gap-3">
            <div class="w-8 h-8 border-2 border-gray-200 border-t-black rounded-full animate-spin"></div>
            <span class="text-sm text-gray-500">Loading profile…</span>
        </div>
    </div>

    <div id="profile" class="hidden space-y-6">
        {{-- Profile Header Card --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-gray-900 via-gray-800 to-gray-700"></div>
            <div class="px-6 pb-6">
                <div class="flex items-end gap-4 -mt-10">
                    <div id="avatarInitial" class="w-20 h-20 rounded-xl bg-black text-white flex items-center justify-center font-bold text-2xl border-4 border-white shadow-lg flex-shrink-0"></div>
                    <div class="flex-1 min-w-0 pb-1">
                        <h2 id="name" class="text-xl font-bold text-gray-900 truncate"></h2>
                        <p id="email" class="text-sm text-gray-500 truncate"></p>
                    </div>
                    <div class="flex-shrink-0 pb-1">
                        <span id="roleBadge" class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Books</div>
                        <div id="bookCount" class="text-xl font-bold">-</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Permissions</div>
                        <div id="permissionsCount" class="text-xl font-bold">-</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Status</div>
                        <div id="statusValue" class="text-xl font-bold">-</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Subscription</div>
                        <div id="subscriptionValue" class="text-xl font-bold">-</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Account Details --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                Account Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Full Name</span>
                    <span id="detailName" class="text-sm font-medium text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Email Address</span>
                    <span id="detailEmail" class="text-sm font-medium text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Role</span>
                    <span id="detailRole" class="text-sm font-medium text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Account Status</span>
                    <span id="detailStatus" class="text-sm font-medium text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Subscription</span>
                    <span id="detailSubscription" class="text-sm font-medium text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">User ID</span>
                    <span id="detailId" class="text-sm font-mono text-gray-600 truncate max-w-[200px]">-</span>
                </div>
            </div>
        </div>

        {{-- Subscription & Payments --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                    Subscription &amp; Payments
                </h3>
                <button id="subscribeBtn" type="button" class="hidden px-4 py-2 bg-black text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition disabled:opacity-50">
                    Subscribe via Stripe
                </button>
            </div>
            <p id="subscriptionHint" class="text-sm text-gray-500 mb-4">Subscribe for full library access, or buy individual books below.</p>
            <div id="stripeConfigHint" class="text-xs text-gray-400"></div>
        </div>

        {{-- Author earnings (authors only) --}}
        <div id="authorEarningsSection" class="hidden bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h3 class="text-sm font-semibold text-gray-900">My Author Earnings</h3>
                <a href="/dashboard/my-earnings" class="text-xs text-blue-600 hover:underline">Full details</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="rounded-lg bg-gray-50 p-3 text-center">
                    <div class="text-xs text-gray-500">Sales</div>
                    <div id="authorSalesCount" class="text-lg font-bold">0</div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 text-center">
                    <div class="text-xs text-gray-500">Gross</div>
                    <div id="authorGross" class="text-lg font-bold">$0.00</div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 text-center">
                    <div class="text-xs text-gray-500">Platform Fee</div>
                    <div id="authorPlatformFee" class="text-lg font-bold text-purple-700">$0.00</div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 text-center">
                    <div class="text-xs text-gray-500">You Receive</div>
                    <div id="authorNetEarnings" class="text-lg font-bold text-green-700">$0.00</div>
                </div>
            </div>
            <div id="authorSalesList" class="space-y-2 text-sm text-gray-400">No sales yet.</div>
        </div>

        {{-- Buy Books --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                Buy Books
            </h3>
            <div id="booksList" class="space-y-2">
                <span class="text-sm text-gray-400">Loading books…</span>
            </div>
        </div>

        {{-- My Purchases --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                My Purchases
            </h3>
            <div id="purchasesList" class="space-y-2">
                <span class="text-sm text-gray-400">Loading purchases…</span>
            </div>
        </div>

        {{-- Permissions Section --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                Your Permissions
            </h3>
            <div id="permissionBadges" class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-400">Loading…</span>
            </div>
        </div>
    </div>

    <div id="error" class="hidden mt-6 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
        <span id="errorText">Failed to load profile</span>
    </div>
</div>
@endsection