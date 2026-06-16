@extends('main')

@section('title', 'Notifications')

@section('content')
<div class="max-w-3xl mx-auto" id="notificationsPage">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Notifications</h1>
            <p class="text-sm text-gray-500">Book orders, likes, comments, purchases, and platform alerts — live via WebSocket</p>
        </div>
        <button type="button" id="markAllReadBtn" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Mark all read</button>
    </div>

    <div class="mb-3 flex items-center gap-4 text-xs text-gray-500">
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-600"></span> Unread</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gray-300"></span> Read</span>
    </div>

    <div id="notificationsList" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 min-h-[200px]">
        <div class="px-4 py-8 text-center text-gray-400 text-sm">Loading notifications…</div>
    </div>
    <div class="mt-4 text-center">
        <button type="button" id="notificationPageSeeMoreBtn" class="hidden px-4 py-2 text-sm font-medium border border-gray-200 rounded-lg hover:bg-gray-50 transition">See more</button>
    </div>
</div>
@endsection
