@extends('main')

@section('title', 'Edit Bank Account')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-semibold mb-6">Edit Bank Account</h1>

    <form method="POST" action="{{ route('dashboard.account.bank.update', array_filter(['bank' => $bankAccount, 'user_id' => request('user_id')])) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        @csrf
        @method('PUT')
        @include('dashboard.account.bank._form', ['account' => $bankAccount])
        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-black text-white rounded-lg">Update</button>
            <a href="{{ route('dashboard.account.bank.index', request('user_id') ? ['user_id' => request('user_id')] : []) }}" class="px-4 py-2 border rounded-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
