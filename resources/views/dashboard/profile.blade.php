<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Profile</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/home.js'])
</head>

<body class="min-h-screen bg-white text-[#1b1b18]">
    <div class="flex min-h-screen">
        @include('components.aside')

        <div class="flex-1 flex min-w-0 flex-col">
            @include('components.header')

            <main class="flex-1 p-6">
                <div class="max-w-5xl mx-auto">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h1 class="text-2xl font-semibold">My Profile</h1>
                            <p class="text-sm text-gray-600">Account details</p>
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
                                Token is stored in <span class="font-mono">sessionStorage/localStorage</span>.
                            </div>

                            <div class="mt-4 text-xs text-gray-500">
                                Use the avatar button in the header dropdown to logout.
                            </div>
                        </div>

                        <div id="error" class="hidden mt-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>