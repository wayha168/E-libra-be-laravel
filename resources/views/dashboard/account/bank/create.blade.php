@extends('main')

@section('title', 'Add Bank Account')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-semibold mb-6">Add Bank Account</h1>

    <form method="POST" action="{{ route('dashboard.account.bank.store', request('user_id') ? ['user_id' => request('user_id')] : []) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        @csrf
        @if(request('user_id'))
        <input type="hidden" name="user_id" value="{{ request('user_id') }}">
        @endif
        @include('dashboard.account.bank._form')
        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-black text-white rounded-lg">Save</button>
            <a href="{{ route('dashboard.account.bank.index', request('user_id') ? ['user_id' => request('user_id')] : []) }}" class="px-4 py-2 border rounded-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
