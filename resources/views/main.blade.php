<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name', 'e-Libra'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-[#1b1b18]">
    <div class="flex min-h-screen">
        @include('components.aside')

        <div class="flex-1 flex min-w-0 flex-col">
            @include('components.header')

            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>