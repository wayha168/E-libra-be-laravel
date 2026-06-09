<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'e-Libra') }} - Login</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-white flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <div class="mx-auto w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold text-xs tracking-widest">eL</div>
            <h1 class="mt-4 text-2xl font-semibold text-black">Welcome back</h1>
            <p class="mt-1 text-gray-500 text-sm">Login to continue</p>
        </div>

        <form id="loginForm" class="space-y-5" autocomplete="off">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2 uppercase tracking-wide">Email</label>
                <input name="email" type="email" required
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent" placeholder="you@example.com" />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2 uppercase tracking-wide">Password</label>
                <input name="password" type="password" required
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent" placeholder="••••••••" />
            </div>

            <button type="submit" id="submitBtn"
                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-black text-white py-2.5 font-medium text-sm hover:bg-gray-800 transition disabled:opacity-50">
                <span id="btnText">Login</span>
                <span id="btnSpinner" class="hidden" aria-hidden="true">⏳</span>
            </button>

            <div id="errorBox" class="hidden rounded-md border border-gray-300 bg-gray-50 text-gray-700 px-3 py-2.5 text-xs"></div>
        </form>

        <div class="mt-6 text-center text-xs text-gray-400">
            By logging in, you accept our <a class="underline hover:text-gray-600" href="#">terms</a>.
        </div>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const errorBox = document.getElementById('errorBox');
        const submitBtn = document.getElementById('submitBtn');
        const btnSpinner = document.getElementById('btnSpinner');
        const btnText = document.getElementById('btnText');

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.classList.add('hidden');

            submitBtn.disabled = true;
            btnSpinner.classList.remove('hidden');
            btnText.textContent = 'Signing in...';

            const body = {
                email: form.email.value,
                password: form.password.value,
            };

            try {
                const res = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });

                const data = await res.json().catch(() => null);

                if (!res.ok) {
                    showError(data?.message || 'Invalid credentials');
                    return;
                }

                const token = data?.data?.token;
                if (!token) {
                    showError('Token missing from response');
                    return;
                }

                localStorage.setItem('api_token', token);
                window.location.href = '/home';
            } catch (err) {
                showError('Network error. Please try again.');
            } finally {
                submitBtn.disabled = false;
                btnSpinner.classList.add('hidden');
                btnText.textContent = 'Login';
            }
        });
    </script>
</body>
</html>