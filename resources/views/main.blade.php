<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name', 'e-Libra'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen bg-white text-[#1b1b18]">
    <div class="flex h-full">
        @include('components.aside')

        <div class="flex-1 min-w-0 flex flex-col">
            @include('components.header')

            <main class="flex-1 overflow-y-auto">
                <div class="p-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>

</html>