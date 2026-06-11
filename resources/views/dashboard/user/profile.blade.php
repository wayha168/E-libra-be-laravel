@extends('main')

@section('title', 'profile')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Profile</h1>
            <p class="text-sm text-gray-600">Your account details</p>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
        <div id="loading" class="text-sm text-gray-600">Loading...</div>

        <div id="profile" class="hidden">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 rounded-full bg-black text-white flex items-center justify-center font-bold text-lg" id="avatarInitial"></div>
                <div>
                    <div id="name" class="text-lg font-semibold"></div>
                    <div id="email" class="text-sm text-gray-600"></div>
                    <div id="role" class="text-xs text-gray-400 mt-0.5"></div>
                </div>
            </div>

            <hr class="border-gray-100 mb-5" />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">Books</div>
                    <div id="bookCount" class="mt-1 text-2xl font-semibold">-</div>
                </div>
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500">Permissions</div>
                    <div class="mt-1 text-sm text-gray-700" id="permissionBadges">
                        <span class="text-gray-400">-</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="error" class="hidden mt-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('/api/user/profile')
        .then(r => r.json())
        .then(data => {
            if (data.user) {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('profile').classList.remove('hidden');
                document.getElementById('avatarInitial').textContent = data.user.name.charAt(0).toUpperCase();
                document.getElementById('name').textContent = data.user.name;
                document.getElementById('email').textContent = data.user.email;
                document.getElementById('role').textContent = data.user.role;
                document.getElementById('bookCount').textContent = data.user.books_count ?? 0;

                const badges = document.getElementById('permissionBadges');
                if (data.permissions && data.permissions.length > 0) {
                    badges.innerHTML = data.permissions.map(p =>
                        `<span class="inline-flex px-2 py-0.5 mr-1 mb-1 rounded text-xs bg-green-50 text-green-700">${p.display_name}</span>`
                    ).join('');
                } else {
                    badges.innerHTML = '<span class="text-gray-400">-</span>';
                }
            }
        })
        .catch(err => {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').classList.remove('hidden');
            document.getElementById('error').textContent = 'Failed to load profile';
        });
});
</script>
@endpush