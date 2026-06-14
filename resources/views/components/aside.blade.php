<aside id="dashboardAside" class="w-64 h-screen bg-white border-r border-gray-200 flex flex-col flex-shrink-0 relative">
    <div id="asideTopBar" class="flex items-center justify-between gap-2 px-4 py-4 flex-shrink-0 border-b border-gray-100">
        <div class="flex items-center gap-2.5 min-w-0 aside-brand">
            <div class="w-9 h-9 rounded-xl bg-black text-white flex items-center justify-center font-bold text-sm shrink-0">eL</div>
            <div class="aside-brand-text min-w-0">
                <div class="text-sm font-semibold truncate">e-Libra</div>
                <div class="text-xs text-gray-500 truncate">Dashboard</div>
            </div>
        </div>
        <button id="asideToggleBtn" type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition shrink-0" aria-expanded="true" aria-label="Close sidebar" title="Close sidebar"></button>
    </div>

    <div class="aside-inner flex flex-col flex-1 min-h-0 p-4 pt-3 overflow-hidden">
    <nav class="space-y-1 flex-1 min-h-0 overflow-y-auto">

        <a href="{{ route('dashboard.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.index') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            Overview
        </a>
        <a href="{{ route('dashboard.categories.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.categories.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
            </svg>
            Categories
        </a>
        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
        <a href="{{ route('dashboard.authors.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.authors.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            Authors
        </a>
        @endif
        <a href="{{ route('dashboard.books.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.books.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
        @if(auth()->user()->isAuthor() && !auth()->user()->isAdmin() && !auth()->user()->isSuperAdmin())
            My Books
        @else
            Books
        @endif
        </a>
        @if(auth()->user()->isAuthor() || auth()->user()->authorProfile)
        <a href="{{ route('dashboard.earnings.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.earnings.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            My Earnings
        </a>
        @endif
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
        <a href="{{ route('dashboard.purchases.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.purchases.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
            </svg>
            Purchases
        </a>
        @endif
        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
        <a href="{{ route('dashboard.images.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.images.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
            </svg>
            Images
        </a>
        @endif

        @auth
        @if(auth()->user()->isStaff())
        <div class="pt-3 mt-3 border-t border-gray-100">
            <div class="text-[10px] uppercase tracking-widest text-gray-400 font-semibold px-3 mb-1">Account Settings</div>

            <a href="{{ route('dashboard.account.bank.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard.account.bank.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.375c0 .621-.504 1.125-1.125 1.125H21.75m-9 6.75h.008v.008H12v-.008Z" /></svg>
                Bank Details
            </a>
            <a href="{{ route('dashboard.account.notifications.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard.account.notifications.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                Notifications
            </a>
            <a href="{{ route('dashboard.account.activity.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard.account.activity.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                User Activity
            </a>

        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
            <div class="aside-accordion-group" data-accordion="account-settings">
                <button type="button" class="aside-accordion-btn flex items-center justify-between w-full px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard.users.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition" data-accordion-target="users-submenu">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        Users
                    </span>
                    <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 aside-accordion-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div id="users-submenu" class="aside-accordion-panel overflow-hidden transition-all duration-200 max-h-0" style="max-height:0">
                    <div class="ml-6 mt-1 space-y-0.5 border-l-2 border-gray-200 pl-3">
                        <a href="{{ route('dashboard.users.index') }}" class="block px-2 py-1.5 rounded text-sm {{ request()->routeIs('dashboard.users.index') ? 'text-black font-medium' : 'text-gray-600 hover:text-black hover:bg-gray-50' }} transition">All Users</a>
                        <a href="{{ route('dashboard.users.create') }}" class="block px-2 py-1.5 rounded text-sm {{ request()->routeIs('dashboard.users.create') ? 'text-black font-medium' : 'text-gray-600 hover:text-black hover:bg-gray-50' }} transition">Add User</a>
                    </div>
                </div>
            </div>

            {{-- Permissions --}}
            <div class="aside-accordion-group" data-accordion="account-settings">
                <button type="button" class="aside-accordion-btn flex items-center justify-between w-full px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard.permissions.*') ? 'bg-black/5 text-black font-medium' : 'text-gray-700 hover:bg-gray-100' }} transition" data-accordion-target="permissions-submenu">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                        Permissions
                    </span>
                    <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 aside-accordion-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div id="permissions-submenu" class="aside-accordion-panel overflow-hidden transition-all duration-200 max-h-0" style="max-height:0">
                    <div class="ml-6 mt-1 space-y-0.5 border-l-2 border-gray-200 pl-3">
                        <a href="{{ route('dashboard.permissions.index') }}" class="block px-2 py-1.5 rounded text-sm {{ request()->routeIs('dashboard.permissions.index') ? 'text-black font-medium' : 'text-gray-600 hover:text-black hover:bg-gray-50' }} transition">All Permissions</a>
                        <a href="{{ route('dashboard.permissions.create') }}" class="block px-2 py-1.5 rounded text-sm {{ request()->routeIs('dashboard.permissions.create') ? 'text-black font-medium' : 'text-gray-600 hover:text-black hover:bg-gray-50' }} transition">Add Permission</a>
                    </div>
                </div>
            </div>
        @endif
        </div>
        @endif
        @endauth
    </nav>
    </div>

    <div class="p-4 pt-0 flex-shrink-0">
        <button id="logoutBtn" class="w-full rounded-lg bg-black text-white px-4 py-2 font-semibold hover:bg-black/90 transition">
            Logout
        </button>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Accordion logic: clicking one closes the others in the same group
        const btns = document.querySelectorAll('.aside-accordion-btn');

        btns.forEach(function(btn) {
            const targetId = btn.getAttribute('data-accordion-target');
            const panel = document.getElementById(targetId);
            const group = btn.closest('[data-accordion]');
            const accordionName = group ? group.getAttribute('data-accordion') : null;

            // Auto-open if current route matches
            const isActive = btn.classList.contains('bg-black/5');
            if (isActive && panel) {
                panel.style.maxHeight = panel.scrollHeight + 'px';
                const chevron = btn.querySelector('.aside-accordion-chevron');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
            }

            btn.addEventListener('click', function() {
                const isOpen = panel && panel.style.maxHeight && panel.style.maxHeight !== '0px';

                // Close all siblings in same accordion group
                if (accordionName) {
                    document.querySelectorAll('[data-accordion="' + accordionName + '"]').forEach(function(sibling) {
                        const sibPanel = sibling.querySelector('.aside-accordion-panel');
                        const sibChevron = sibling.querySelector('.aside-accordion-chevron');
                        if (sibPanel) sibPanel.style.maxHeight = '0px';
                        if (sibChevron) sibChevron.style.transform = 'rotate(0deg)';
                    });
                }

                // Toggle clicked one
                if (!isOpen && panel) {
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                    const chevron = btn.querySelector('.aside-accordion-chevron');
                    if (chevron) chevron.style.transform = 'rotate(180deg)';
                }
            });
        });
    });
</script>