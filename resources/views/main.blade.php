<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name', 'e-Libra'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #dashboardAside {
            transition: width 0.25s ease, min-width 0.25s ease;
        }

        #dashboardAside.aside-is-collapsed {
            width: 3.5rem !important;
            min-width: 3.5rem !important;
            overflow: visible;
            z-index: 20;
        }

        #dashboardAside.aside-is-collapsed .aside-inner,
        #dashboardAside.aside-is-collapsed .aside-brand-text {
            display: none;
        }

        #dashboardAside.aside-is-collapsed .aside-brand {
            display: none;
        }

        #dashboardAside.aside-is-collapsed #asideTopBar {
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            padding: 0.875rem 0.375rem;
            border-bottom: none;
            min-height: 3.5rem;
        }

        #dashboardAside.aside-is-collapsed #asideToggleBtn {
            width: 2.5rem;
            height: 2.5rem;
            background: #fff;
            border-color: #e5e7eb;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        #dashboardAside.aside-is-collapsed #asideToggleBtn svg {
            color: #374151;
        }

        #dashboardAside.aside-is-collapsed .aside-brand .w-9 {
            width: 2.25rem;
            height: 2.25rem;
            font-size: 0.65rem;
        }

        #dashboardAside.aside-is-collapsed > div:last-child {
            display: none;
        }

        #dashboardLayout.aside-collapsed #headerMain {
            padding-left: 0;
        }

        /* Notification panel — slide in from right */
        #notificationSidebar {
            right: 0;
            left: auto;
            transform: translateX(100%);
        }

        #notificationSidebar.open {
            transform: translateX(0);
        }

        #notificationSidebarBackdrop {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        #notificationSidebarBackdrop.open {
            display: block;
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>

<body class="h-screen bg-white text-[#1b1b18] overflow-hidden">
    <div id="dashboardLayout" class="flex h-full">
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

    @auth
    @if(auth()->user()?->isStaff())
    <div id="notificationSidebarBackdrop" class="hidden fixed inset-0 bg-black/30 z-[60]"></div>
    <aside id="notificationSidebar" class="fixed inset-y-0 right-0 w-full max-w-md bg-white border-l border-gray-200 shadow-2xl z-[70] transition-transform duration-300 ease-out flex flex-col" aria-label="Notifications panel">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3 flex-shrink-0">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Notifications</h2>
                <p class="text-xs text-gray-500">Orders, recommendations &amp; alerts</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="markAllReadSidebarBtn" class="text-xs text-blue-600 hover:underline">Mark all read</button>
                <button type="button" id="notificationSidebarClose" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 hover:bg-gray-50" aria-label="Close notifications">
                    <svg class="w-5 h-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
        <div id="notificationSidebarList" class="flex-1 overflow-y-auto divide-y divide-gray-100 text-sm"></div>
        <div class="px-5 py-3 border-t border-gray-100 flex-shrink-0 space-y-2">
            <button type="button" id="notificationSeeMoreBtn" class="hidden w-full py-2 text-sm font-medium text-center border border-gray-200 rounded-lg hover:bg-gray-50 transition">See more</button>
            <a href="{{ route('dashboard.account.notifications.index') }}" class="block text-sm text-blue-600 hover:underline">View all notifications</a>
        </div>
    </aside>
    @endif
    @endauth
</body>

</html>
