@extends('main')



@section('title', 'Dashboard')



@section('content')

@php

    $isAuthorOnly = auth()->user()->isAuthor() && !auth()->user()->isAdmin() && !auth()->user()->isSuperAdmin();

@endphp

<div class="max-w-6xl mx-auto">

    <div class="mb-6 flex items-center justify-between gap-3">

        <div>

            <h1 class="text-2xl font-semibold">{{ $isAuthorOnly ? 'Author Dashboard' : 'Dashboard Overview' }}</h1>

            <p id="overviewSubtitle" class="text-sm text-gray-500">

                {{ $isAuthorOnly ? 'Your books, categories, and sales at a glance' : 'Live summary of categories, books, users, and purchases' }}

            </p>

        </div>

        <span id="liveIndicator" class="hidden inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-100">

            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>

            Live

        </span>

    </div>



    <div id="overviewLoading" class="flex items-center justify-center py-16">

        <div class="flex flex-col items-center gap-3">

            <div class="w-8 h-8 border-2 border-gray-200 border-t-black rounded-full animate-spin"></div>

            <span class="text-sm text-gray-500">Loading overview…</span>

        </div>

    </div>



    <div id="overviewContent" class="hidden space-y-6" data-default-scope="{{ $isAuthorOnly ? 'author' : 'admin' }}">

        {{-- Row 1 --}}

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="bg-white rounded-xl border border-gray-200 p-5">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">

                        <svg class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>

                    </div>

                    <div>

                        <div id="labelBooks" class="text-xs text-gray-500 uppercase tracking-wide">Books</div>

                        <div id="bookCount" class="text-2xl font-bold">-</div>

                    </div>

                </div>

            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">

                        <svg class="w-5 h-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z"/></svg>

                    </div>

                    <div>

                        <div id="labelCategories" class="text-xs text-gray-500 uppercase tracking-wide">Categories</div>

                        <div id="categoryCount" class="text-2xl font-bold">-</div>

                    </div>

                </div>

            </div>

            <div id="cardUsers" class="bg-white rounded-xl border border-gray-200 p-5">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-lg bg-violet-50 flex items-center justify-center">

                        <svg class="w-5 h-5 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>

                    </div>

                    <div>

                        <div id="labelUsers" class="text-xs text-gray-500 uppercase tracking-wide">Registered Users</div>

                        <div id="userCount" class="text-2xl font-bold">-</div>

                    </div>

                </div>

            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">

                        <svg class="w-5 h-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>

                    </div>

                    <div>

                        <div id="labelRevenue" class="text-xs text-gray-500 uppercase tracking-wide">Revenue</div>

                        <div id="revenueCount" class="text-2xl font-bold">-</div>

                    </div>

                </div>

            </div>

        </div>



        {{-- Row 2 --}}

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="bg-white rounded-xl border border-gray-200 p-5">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">

                        <svg class="w-5 h-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>

                    </div>

                    <div>

                        <div id="labelPaid" class="text-xs text-gray-500 uppercase tracking-wide">Paid Purchases</div>

                        <div id="purchasesPaidCount" class="text-2xl font-bold text-green-700">-</div>

                    </div>

                </div>

            </div>

            <div id="cardPending" class="bg-white rounded-xl border border-gray-200 p-5">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center">

                        <svg class="w-5 h-5 text-orange-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>

                    </div>

                    <div>

                        <div id="labelPending" class="text-xs text-gray-500 uppercase tracking-wide">Pending Purchases</div>

                        <div id="purchasesPendingCount" class="text-2xl font-bold text-amber-600">-</div>

                    </div>

                </div>

            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-lg bg-sky-50 flex items-center justify-center">

                        <svg class="w-5 h-5 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>

                    </div>

                    <div>

                        <div id="labelComments" class="text-xs text-gray-500 uppercase tracking-wide">Book Comments</div>

                        <div id="commentsCount" class="text-2xl font-bold">-</div>

                    </div>

                </div>

            </div>

            <div id="cardCommission" class="bg-white rounded-xl border border-gray-200 p-5">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">

                        <svg class="w-5 h-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>

                    </div>

                    <div>

                        <div id="labelCommission" class="text-xs text-gray-500 uppercase tracking-wide">Admin Commission (10%)</div>

                        <div id="adminCommissionCount" class="text-2xl font-bold text-purple-700">-</div>

                    </div>

                </div>

            </div>

        </div>



        {{-- Charts --}}

        <div class="bg-white rounded-xl border border-gray-200 p-6">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">

                <div>

                    <h2 class="text-sm font-semibold text-gray-900">Analytics Charts</h2>

                    <p id="chartsSubtitle" class="text-xs text-gray-500">Income, registrations, purchases, and library stats</p>

                </div>

                <div class="flex flex-wrap gap-2">

                    <select id="chartPeriod" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">

                        <option value="7d">Last 7 days</option>

                        <option value="30d">Last 30 days</option>

                        <option value="6m" selected>Last 6 months</option>

                        <option value="12m">Last 12 months</option>

                    </select>

                </div>

            </div>



            <div id="chartTabs" class="flex flex-wrap gap-2 mb-4">

                <button type="button" data-chart-tab="income" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-black text-white">Income</button>

                <button type="button" data-chart-tab="users" data-admin-only class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 text-gray-700">Registrations</button>

                <button type="button" data-chart-tab="purchases" data-admin-only class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 text-gray-700">Purchases</button>

                <button type="button" data-chart-tab="library" data-admin-only class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 text-gray-700">Library &amp; Status</button>

            </div>



            <div data-chart-panel="income" class="h-72">

                <canvas id="chartIncome"></canvas>

            </div>

            <div data-chart-panel="users" class="h-72 hidden">

                <canvas id="chartUsers"></canvas>

            </div>

            <div data-chart-panel="purchases" class="h-72 hidden">

                <canvas id="chartPurchases"></canvas>

            </div>

            <div data-chart-panel="library" class="hidden">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="h-64"><canvas id="chartLibrary"></canvas></div>

                    <div class="h-64"><canvas id="chartPurchaseStatus"></canvas></div>

                </div>

            </div>

        </div>



        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <div id="sectionRecentUsers" class="bg-white rounded-xl border border-gray-200 p-6">

                <h2 class="text-sm font-semibold text-gray-900 mb-4">Recent Registered Users</h2>

                <div id="recentUsers" class="space-y-2 text-sm text-gray-400">Loading…</div>

            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">

                <h2 id="recentSalesTitle" class="text-sm font-semibold text-gray-900 mb-4">Recent Purchases <span class="text-xs font-normal text-gray-400">(live updates)</span></h2>

                <div id="recentPurchases" class="space-y-2 text-sm text-gray-400">Loading…</div>

            </div>

        </div>



        <div class="bg-white rounded-xl border border-gray-200 p-6">

            <h2 id="recentCommentsTitle" class="text-sm font-semibold text-gray-900 mb-4">Recent Book Feedback</h2>

            <div id="recentComments" class="space-y-2 text-sm text-gray-400">Loading…</div>

        </div>



        <div class="bg-white rounded-xl border border-gray-200 p-6">

            <div class="flex items-center justify-between gap-3 mb-4">

                <div>

                    <h2 class="text-sm font-semibold text-gray-900">Recommended For You</h2>

                    <p class="text-xs text-gray-500">Ranked by paid purchases and your reading history</p>

                </div>

            </div>

            <div id="recommendationsList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm text-gray-400">Loading…</div>

        </div>

    </div>



    <div id="overviewError" class="hidden mt-6 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"></div>

</div>

@endsection

