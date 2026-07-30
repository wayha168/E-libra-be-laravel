@extends('main')

@section('title', 'Add ABA PayWay')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('dashboard.account.payway.index') }}" class="text-sm text-blue-600 hover:underline">← Back to ABA PayWay</a>
        <h1 class="text-2xl font-semibold mt-2">Add ABA PayWay credentials</h1>
        <p class="text-sm text-gray-500">Enter merchant ID and API key provided by ABA Bank for this author or admin.</p>
    </div>

    @if(session('error'))
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.account.payway.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
        @csrf
        @include('dashboard.account.payway._form', ['owners' => $owners, 'prefillUserId' => $prefillUserId])
        <div class="flex justify-end gap-2">
            <a href="{{ route('dashboard.account.payway.index') }}" class="px-4 py-2 rounded-xl border border-gray-200 text-sm hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-black text-white rounded-xl text-sm hover:bg-gray-800">Save credentials</button>
        </div>
    </form>
</div>
@endsection
