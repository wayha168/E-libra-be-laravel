<aside class="w-64 bg-white border-r border-gray-200 p-5 sticky top-0 flex flex-col">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl bg-black text-white flex items-center justify-center font-bold text-sm">eL</div>
        <div>
            <div class="text-sm font-semibold">e-Libra</div>
            <div class="text-xs text-gray-500">Dashboard</div>
        </div>
    </div>

    <nav class="space-y-2">
        <a href="/home" class="block px-3 py-2 rounded-lg bg-black/5 text-black font-medium">Overview</a>
        <a href="/dashboard/books" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Books</a>
        <a href="/dashboard/categories" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Categories</a>
        <a href="/dashboard/images" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Images</a>
          @auth 
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
        <a href="{{ route('dashboard.users.index') }}" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Users</a>
        <a href="{{ route('dashboard.permissions.index') }}" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Permissions</a>
        @endif
        @endauth

    </nav>


    <div class="mt-8">
        <button id="logoutBtn" class="w-full rounded-lg bg-black text-white px-4 py-2 font-semibold hover:bg-black/90 transition">
            Logout
        </button>
    </div>
</aside>