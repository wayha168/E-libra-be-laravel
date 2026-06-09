<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'e-Libra') }} - Home</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Dashboard</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Your account details</p>
            </div>

            <button id="logoutBtn" class="rounded-lg bg-black text-white px-4 py-2 font-semibold hover:bg-black/90 transition">Logout</button>
        </div>

        <div class="mt-6 bg-white dark:bg-[#161615] rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div id="loading" class="text-sm text-gray-600 dark:text-gray-400">Loading...</div>

            <div id="profile" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Name</div>
                        <div id="name" class="mt-2 text-lg font-semibold"></div>
                    </div>

                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Email</div>
                        <div id="email" class="mt-2 text-lg font-semibold"></div>
                    </div>

                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Role</div>
                        <div id="role" class="mt-2 text-lg font-semibold"></div>
                    </div>

                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">ID</div>
                        <div id="id" class="mt-2 text-lg font-semibold"></div>
                    </div>
                </div>

                <div class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                    Token is stored in <span class="font-mono">localStorage</span>. For production, consider httpOnly cookies.
                </div>
            </div>

            <div id="error" class="hidden mt-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"></div>
        </div>
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

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        logoutBtn.addEventListener('click', () => {
            localStorage.removeItem('api_token');
            window.location.href = '/login';
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
                elName.textContent = u?.name || '-';
                elEmail.textContent = u?.email || '-';
                elRole.textContent = u?.role || 'N/A';
                elId.textContent = u?.id ?? '-';

                loading.classList.add('hidden');
                profile.classList.remove('hidden');
            } catch (e) {
                showError('Network error. Please try again.');
            }
        })();
    </script>
</body>

</html>