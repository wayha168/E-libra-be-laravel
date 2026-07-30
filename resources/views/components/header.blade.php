@auth
@php($headerUser = auth()->user())
<header class="app-header w-full sticky top-0 z-30 flex-shrink-0" data-header-hydrated="server">
    <div class="w-full px-6 py-3.5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0 pl-0 aside-collapsed:pl-14 transition-[padding] duration-200" id="headerMain">
            <div class="min-w-0">
                <div id="accountName" class="text-sm font-semibold truncate" style="color: var(--text-primary)">{{ $headerUser?->name ?? 'Account' }}</div>
                <div class="text-xs" style="color: var(--text-secondary)" data-i18n="dashboard">Dashboard</div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Appearance & language (glassmorphism) --}}
            <div class="relative" id="prefsDropdown">
                <button
                    id="prefsDropdownBtn"
                    type="button"
                    class="glass-btn relative inline-flex items-center gap-2 h-10 rounded-xl px-3"
                    aria-label="Appearance & language"
                    aria-expanded="false"
                    aria-haspopup="true"
                    data-i18n="open_preferences"
                    data-i18n-attr="aria-label"
                >
                    <svg class="w-4.5 h-4.5 shrink-0" style="color: var(--brand)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                    </svg>
                    <span id="prefsLangLabel" class="text-xs font-bold tracking-wide" style="color: var(--text-primary)">EN</span>
                    <span aria-hidden="true" class="text-[10px]" style="color: var(--text-muted)">▾</span>
                </button>

                <div id="prefsMenu" class="prefs-menu dropdown-panel hidden absolute right-0 mt-2 p-4 space-y-4" role="menu" aria-label="Appearance & language">
                    <div>
                        <div class="prefs-section-label mb-2.5" data-i18n="theme_color">Theme color</div>
                        <div class="flex items-center gap-2.5">
                            <button type="button" class="theme-swatch" data-theme-pick="emerald" title="Emerald" aria-label="Emerald theme" aria-pressed="false"></button>
                            <button type="button" class="theme-swatch" data-theme-pick="ocean" title="Ocean" aria-label="Ocean theme" aria-pressed="false"></button>
                            <button type="button" class="theme-swatch" data-theme-pick="sunset" title="Sunset" aria-label="Sunset theme" aria-pressed="false"></button>
                            <button type="button" class="theme-swatch" data-theme-pick="violet" title="Violet" aria-label="Violet theme" aria-pressed="false"></button>
                        </div>
                    </div>

                    <div>
                        <div class="prefs-section-label mb-2.5" data-i18n="display_mode">Display</div>
                        <div class="flex gap-2">
                            <button type="button" class="mode-chip" data-mode-pick="light" aria-pressed="false">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                                <span data-i18n="light">Light</span>
                            </button>
                            <button type="button" class="mode-chip" data-mode-pick="dark" aria-pressed="false">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                                <span data-i18n="dark">Dark</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <div class="prefs-section-label mb-2.5" data-i18n="language">Language</div>
                        <div class="flex gap-2">
                            <button type="button" class="lang-chip" data-lang-pick="en" aria-pressed="false">
                                <span data-i18n="english">English</span>
                            </button>
                            <button type="button" class="lang-chip" data-lang-pick="km" aria-pressed="false">
                                <span data-i18n="khmer">ខ្មែរ</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if($headerUser?->isStaff())
            <button type="button" id="notificationBell" data-user-id="{{ $headerUser->id }}" class="glass-btn relative inline-flex items-center justify-center w-10 h-10 rounded-xl" aria-label="Open notifications" data-i18n="open_notifications" data-i18n-attr="aria-label">
                <svg class="w-5 h-5" style="color: var(--text-primary)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                <span id="notificationBadge" class="hidden absolute -top-1 -right-1 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center">0</span>
            </button>

            <div class="relative" id="accountDropdown">
                <button id="accountDropdownBtn" type="button" class="glass-btn inline-flex items-center gap-2 rounded-xl px-2 py-1.5">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--brand-soft)">
                        <span id="accountInitial" class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-semibold text-white" style="background: var(--brand)">{{ strtoupper(mb_substr($headerUser->name, 0, 1)) }}</span>
                    </span>
                    <span class="sr-only" data-i18n="open_account">Open account menu</span>
                    <span aria-hidden="true" style="color: var(--text-muted)">▾</span>
                </button>

                <div id="accountMenu" class="dropdown-panel hidden absolute right-0 mt-2 w-72 rounded-2xl overflow-hidden z-40">
                    <div class="px-4 py-3" style="border-bottom: 1px solid var(--border)">
                        <div class="text-xs" style="color: var(--text-secondary)" data-i18n="signed_in_as">Signed in as</div>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background: var(--brand-soft)">
                                <span class="text-sm font-semibold" style="color: var(--brand-strong)">{{ strtoupper(mb_substr($headerUser->name, 0, 1)) }}</span>
                            </div>
                            <div class="min-w-0">
                                <div id="accountEmail" class="text-sm font-semibold truncate" style="color: var(--text-primary)">{{ $headerUser->email }}</div>
                                <div id="accountRole" class="text-xs" style="color: var(--text-secondary)">{{ $headerUser->display_role }}</div>
                            </div>
                        </div>
                    </div>

                    <a href="/profile" class="block px-4 py-3 text-sm transition" style="color: var(--text-primary)" data-i18n="profile">Profile</a>

                    <button id="logoutMenuBtn" type="button" class="w-full text-left block px-4 py-3 text-sm transition" style="color: var(--text-primary)" data-i18n="logout">
                        Logout
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</header>
@else
<header class="app-header w-full sticky top-0 z-30 flex-shrink-0">
    <div class="w-full px-6 py-3.5 flex items-center justify-between gap-4">
        <div class="text-sm font-semibold" style="color: var(--text-primary)">e-Libra</div>

        <div class="relative" id="prefsDropdown">
            <button
                id="prefsDropdownBtn"
                type="button"
                class="glass-btn relative inline-flex items-center gap-2 h-10 rounded-xl px-3"
                aria-label="Appearance & language"
                aria-expanded="false"
                aria-haspopup="true"
            >
                <svg class="w-4.5 h-4.5 shrink-0" style="color: var(--brand)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                </svg>
                <span id="prefsLangLabel" class="text-xs font-bold tracking-wide" style="color: var(--text-primary)">EN</span>
                <span aria-hidden="true" class="text-[10px]" style="color: var(--text-muted)">▾</span>
            </button>

            <div id="prefsMenu" class="prefs-menu dropdown-panel hidden absolute right-0 mt-2 p-4 space-y-4" role="menu">
                <div>
                    <div class="prefs-section-label mb-2.5" data-i18n="theme_color">Theme color</div>
                    <div class="flex items-center gap-2.5">
                        <button type="button" class="theme-swatch" data-theme-pick="emerald" title="Emerald" aria-label="Emerald theme"></button>
                        <button type="button" class="theme-swatch" data-theme-pick="ocean" title="Ocean" aria-label="Ocean theme"></button>
                        <button type="button" class="theme-swatch" data-theme-pick="sunset" title="Sunset" aria-label="Sunset theme"></button>
                        <button type="button" class="theme-swatch" data-theme-pick="violet" title="Violet" aria-label="Violet theme"></button>
                    </div>
                </div>
                <div>
                    <div class="prefs-section-label mb-2.5" data-i18n="display_mode">Display</div>
                    <div class="flex gap-2">
                        <button type="button" class="mode-chip" data-mode-pick="light"><span data-i18n="light">Light</span></button>
                        <button type="button" class="mode-chip" data-mode-pick="dark"><span data-i18n="dark">Dark</span></button>
                    </div>
                </div>
                <div>
                    <div class="prefs-section-label mb-2.5" data-i18n="language">Language</div>
                    <div class="flex gap-2">
                        <button type="button" class="lang-chip" data-lang-pick="en"><span data-i18n="english">English</span></button>
                        <button type="button" class="lang-chip" data-lang-pick="km"><span data-i18n="khmer">ខ្មែរ</span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
@endauth
