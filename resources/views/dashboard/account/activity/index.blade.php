@extends('main')

@section('title', 'User Activity')

@section('content')
<div class="max-w-5xl mx-auto" id="activityPage">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">User Activity</h1>
            <p class="text-sm text-gray-500">Live log of purchases, orders, and account events</p>
        </div>
        <span id="activityLiveBadge" class="hidden inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-100">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
            Live
        </span>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <x-search-filter
            :action="route('dashboard.account.activity.index')"
            placeholder="Search activity…"
            :filters="[[
                'name' => 'type',
                'options' => [
                    '' => 'All types',
                    'purchase.pending' => 'Pending orders',
                    'purchase.paid' => 'Paid purchases',
                    'book.liked' => 'Book likes',
                    'book.commented' => 'Book comments',
                ],
            ]]"
        />
    </div>

    <div id="activityList" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        @forelse($activities as $activity)
        <div class="px-4 py-3 flex items-start justify-between gap-3" data-activity-id="{{ $activity->id }}">
            <div>
                <div class="font-medium text-gray-900">{{ $activity->title }}</div>
                <div class="text-sm text-gray-600 mt-0.5">{{ $activity->description }}</div>
                <div class="text-xs text-gray-400 mt-1">
                    {{ $activity->actor?->name ?? $activity->user?->name ?? 'System' }}
                    · {{ $activity->created_at->diffForHumans() }}
                </div>
            </div>
            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">{{ $activity->type }}</span>
        </div>
        @empty
        <div class="px-4 py-8 text-center text-gray-400">No activity recorded yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
