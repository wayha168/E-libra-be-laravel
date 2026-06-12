<aside class="w-64 h-screen bg-white border-r border-gray-200 p-5 flex flex-col flex-shrink-0">
    <div class="flex items-center gap-3 mb-6 flex-shrink-0">
        <div class="w-10 h-10 rounded-xl bg-black text-white flex items-center justify-center font-bold text-sm">eL</div>
        <div>
            <div class="text-sm font-semibold">e-Libra</div>
            <div class="text-xs text-gray-500">Dashboard</div>
        </div>
    </div>

    <nav class="space-y-2">
        <a href="{{ route('dashboard.index') }}" class="block px-3 py-2 rounded-lg bg-black/5 text-black font-medium">Overview</a>
        <a href="{{ route('dashboard.categories.index') }}" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Categories</a>
        <a href="{{ route('dashboard.authors.index') }}" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Authors</a>
        <a href="{{ route('dashboard.books.index') }}" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Books</a>
        <a href="{{ route('dashboard.images.index') }}" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Images</a>
        @auth
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
        <a href="{{ route('dashboard.users.index') }}" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Users</a>
        <a href="{{ route('dashboard.permissions.index') }}" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Permissions</a>
        @endif
        @endauth
    </nav>

    <div class="mt-8 flex-shrink-0">
        <button id="logoutBtn" class="w-full rounded-lg bg-black text-white px-4 py-2 font-semibold hover:bg-black/90 transition">
            Logout
        </button>
    </div>
</aside>