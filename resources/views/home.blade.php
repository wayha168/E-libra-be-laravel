@extends('main')

@section('title', config('app.name', 'e-Libra') . ' - Home')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <p class="text-sm text-gray-600">Your account details</p>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
        <div id="loading" class="text-sm text-gray-600">Loading...</div>

        <div id="profile" class="hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">Name</div>
                    <div id="name" class="mt-2 text-lg font-semibold"></div>
                </div>

                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">Email</div>
                    <div id="email" class="mt-2 text-lg font-semibold"></div>
                </div>

                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">Role</div>
                    <div id="role" class="mt-2 text-lg font-semibold"></div>
                </div>

                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">ID</div>
                    <div id="id" class="mt-2 text-lg font-semibold"></div>
                </div>
            </div>

            <div class="mt-6 text-sm text-gray-600">
                Token is stored in <span class="font-mono">sessionStorage</span>. For production, consider httpOnly cookies.
            </div>
        </div>

        <div id="error" class="hidden mt-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"></div>
    </div>
</div>
@endsection