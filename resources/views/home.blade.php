<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'e-Libra') }} - Home</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-[#1b1b18]">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="w-full bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-black text-white flex items-center justify-center font-bold text-sm">eL</div>
                    <div class="min-w-0">
                        <div id="accountName" class="text-sm font-semibold truncate">Account</div>
                        <div class="text-xs text-gray-500">Dashboard</div>
                    </div>
                </div>

                <div class="relative" id="accountDropdown">
                    <button id="accountDropdownBtn" type="button" class="inline-flex items-center gap-2 rounded-xl px-2 py-2 border border-gray-200 bg-white hover:bg-gray-50 transition">
                        <span class="w-8 h-8 rounded-full bg-black/5 flex items-center justify-center">
                            <span class="w-7 h-7 rounded-full bg-black text-white flex items-center justify-center text-sm font-semibold">U</span>
                        </span>
                        <span class="sr-only">Open account menu</span>
                        <span aria-hidden="true" class="text-gray-500">▾</span>
                    </button>

                    <div id="accountMenu" class="hidden absolute right-0 mt-2 w-52 rounded-xl border border-gray-200 bg-white shadow-lg overflow-hidden z-20">

                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="text-xs text-gray-500">Signed in as</div>
                            <div id="accountEmail" class="text-sm font-semibold text-[#1b1b18]">-</div>
                        </div>
                        <a href="#" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition">Profile</a>
                        <div class="px-4 py-2 text-xs text-gray-500">
                            Signed in as <span id="accountEmailMenu" class="font-medium">-</span>
                        </div>
                        <button id="logoutMenuBtn" type="button" class="w-full text-left block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition">
                            Logout
                        </button>

                    </div>
                </div>
            </div>
        </header>

        <div class="flex h-screen">
            <!-- Sidebar -->
            <aside class="w-64 bg-white border-r border-gray-200 p-5 h-screen sticky top-0 flex flex-col">

                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-black text-white flex items-center justify-center font-bold text-sm">eL</div>
                    <div>
                        <div class="text-sm font-semibold">e-Libra</div>
                        <div class="text-xs text-gray-500">Dashboard</div>
                    </div>
                </div>


                <nav class="space-y-2">
                    <a href="#" class="block px-3 py-2 rounded-lg bg-black/5 text-black font-medium">Overview</a>
                    <a href="#" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Profile</a>
                    <a href="#" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">Settings</a>
                </nav>

                <div class="mt-8">
                    <button id="logoutBtn" class="w-full rounded-lg bg-black text-white px-4 py-2 font-semibold hover:bg-black/90 transition">
                        Logout
                    </button>
                </div>
            </aside>

            <!-- Main -->
            <main class="flex-1 p-6">
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
                                Token is stored in <span class="font-mono">localStorage</span>. For production, consider httpOnly cookies.
                            </div>

                            <div class="mt-4 text-xs text-gray-500">
                                <span class="font-semibold">Profile icon:</span> using an avatar button in the header.
                            </div>

                        </div>

                        <div id="error" class="hidden mt-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"></div>
                    </div>
                </div>
            </main>
        </div>

        <script>
            const token = localStorage.getItem('api_token');
            const loading = document.getElementById('loading');
            const profile = document.getElementById('profile');
            const errorBox = document.getElementById('error');

            const elName = document.getElementById('name');
            const elEmail = document.getElementById('email');
            const elRole = document.getElementById('role');
            const elId = document.getElementById('id');

            const logoutBtn = document.getElementById('logoutBtn');

            // Header dropdown
            const accountName = document.getElementById('accountName');
            const accountNameShort = document.getElementById('accountNameShort');
            const accountEmail = document.getElementById('accountEmail');
            const dropdownBtn = document.getElementById('accountDropdownBtn');
            const accountMenu = document.getElementById('accountMenu');
            const logoutMenuBtn = document.getElementById('logoutMenuBtn');
            const accountEmailMenu = document.getElementById('accountEmailMenu');


            function showError(message) {
                errorBox.textContent = message;
                errorBox.classList.remove('hidden');
            }

            function doLogout() {
                localStorage.removeItem('api_token');
                window.location.href = '/login';
            }

            logoutBtn.addEventListener('click', doLogout);
            if (logoutMenuBtn) logoutMenuBtn.addEventListener('click', doLogout);

            function toggleDropdown(force) {
                if (!accountMenu) return;
                const shouldOpen = typeof force === 'boolean' ? force : accountMenu.classList.contains('hidden');
                accountMenu.classList.toggle('hidden', !shouldOpen);
            }

            if (dropdownBtn) {
                dropdownBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleDropdown();
                });
            }

            document.addEventListener('click', () => {
                if (accountMenu && !accountMenu.classList.contains('hidden')) {
                    toggleDropdown(false);
                }
            });

            if (!token) {
                window.location.href = '/login';
            }



            (async () => {
                try {
                    const res = await fetch('/api/me', {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token,
                        },
                    });

                    const data = await res.json().catch(() => null);

                    if (!res.ok) {
                        showError(data?.message || 'Unauthorized');
                        localStorage.removeItem('api_token');
                        window.location.href = '/login';
                        return;
                    }

                    const u = data?.data;
                    const name = u?.name || '-';
                    const email = u?.email || '-';
                    const role = u?.role || 'N/A';
                    const id = u?.id ?? '-';

                    elName.textContent = name;
                    elEmail.textContent = email;
                    elRole.textContent = role;
                    elId.textContent = id;

                    if (accountName) accountName.textContent = name;
                    if (accountNameShort) accountNameShort.textContent = name;
                    if (accountEmail) accountEmail.textContent = email;
                    if (accountEmailMenu) accountEmailMenu.textContent = email;



                    loading.classList.add('hidden');
                    profile.classList.remove('hidden');
                } catch (e) {
                    showError('Network error. Please try again.');
                }
            })();
        </script>
</body>

</html>