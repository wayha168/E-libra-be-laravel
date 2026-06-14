<header class="w-full bg-white border-b border-gray-200 sticky top-0 z-10 flex-shrink-0">
    <div class="w-full px-6 py-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0 pl-0 aside-collapsed:pl-14 transition-[padding] duration-200" id="headerMain">
            <div class="min-w-0">
                <div id="accountName" class="text-sm font-semibold truncate">Account</div>
                <div class="text-xs text-gray-500">Dashboard</div>
            </div>
        </div>

        @auth
        @if(auth()->user()?->isStaff())
        <div class="flex items-center gap-2">
            <button type="button" id="notificationBell" class="relative inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 transition" aria-label="Open notifications">
                <svg class="w-5 h-5 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                <span id="notificationBadge" class="hidden absolute -top-1 -right-1 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center">0</span>
            </button>

            <div class="relative" id="accountDropdown">
                <button id="accountDropdownBtn" type="button" class="inline-flex items-center gap-2 rounded-xl px-2 py-2 border border-gray-200 bg-white hover:bg-gray-50 transition">
                    <span class="w-8 h-8 rounded-full bg-black/5 flex items-center justify-center">
                        <span class="w-7 h-7 rounded-full bg-black text-white flex items-center justify-center text-sm font-semibold">U</span>
                    </span>
                    <span class="sr-only">Open account menu</span>
                    <span aria-hidden="true" class="text-gray-500">▾</span>
                </button>

                <div id="accountMenu" class="hidden absolute right-0 mt-2 w-72 rounded-xl border border-gray-200 bg-white shadow-lg overflow-hidden z-20">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="text-xs text-gray-500">Signed in as</div>
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-black/5 flex items-center justify-center">
                                <span id="accountInitial" class="text-sm font-semibold text-[#1b1b18]">U</span>
                            </div>
                            <div>
                                <div id="accountEmail" class="text-sm font-semibold text-[#1b1b18]">-</div>
                                <div id="accountRole" class="text-xs text-gray-500">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="px-4 py-3 border-b border-gray-50">
                        <div class="text-xs text-gray-500 mb-2">Permissions</div>
                        <div id="accountPermissions" class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-black/5 text-gray-700">-</span>
                        </div>
                    </div> -->

                    <a href="/profile" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition">Profile</a>

                    <button id="logoutMenuBtn" type="button" class="w-full text-left block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition">
                        Logout
                    </button>
                </div>
            </div>
        </div>
        @endif
        @endauth
    </div>
</header>
