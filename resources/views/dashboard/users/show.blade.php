@extends('main')

@section('title', 'User Details')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-semibold">User #{{ $user->id }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.users.edit', $user) }}" class="px-3 py-2 bg-black text-white rounded-lg">Edit</a>
            <a href="{{ route('dashboard.users.index') }}" class="px-3 py-2 border rounded-lg">Back</a>
        </div>
    </div>

    <div class="bg-white border rounded-xl p-6 space-y-5">
        <div class="flex items-center gap-4">
            @if($user->profileImage?->url)
            <img src="{{ $user->profileImage->url }}" alt="{{ $user->profileImage->alt_text ?? $user->name }}" class="w-20 h-20 rounded-full object-cover border border-gray-200" />
            @else
            <div class="w-20 h-20 rounded-full bg-black/5 flex items-center justify-center text-2xl font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            @endif
            <div>
                <div class="text-xl font-semibold">{{ $user->name }}</div>
                <div class="text-sm text-gray-600">{{ $user->email }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-gray-500">Role</div>
                <div class="font-medium">{{ $user->display_role }}</div>
            </div>
            <div>
                <div class="text-gray-500">Status</div>
                <div class="font-medium">{{ $user->display_status }}</div>
            </div>
            <div>
                <div class="text-gray-500">Created</div>
                <div class="font-medium">{{ $user->created_at?->format('M d, Y H:i') ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Updated</div>
                <div class="font-medium">{{ $user->updated_at?->format('M d, Y H:i') ?? '-' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
