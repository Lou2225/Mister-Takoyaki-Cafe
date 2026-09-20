<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
@php
    $user      = auth()->user();
    $roleTheme = $user?->getRoleTheme() ?? [];
    $roleClass = 'role-' . strtolower($roleTheme['primary'] ?? 'guest');


    $iconColor = match ((int) $user->role_id) {
        1       => 'text-indigo-500',
        2       => 'text-rose-500',
        default => 'text-emerald-500',
    };

    $firstName = $user->first_name  ?? '';
    $lastName  = $user->last_name   ?? '';
    $roleName  = $user->role?->name ?? 'Admin';
    $email     = $user->email       ?? '';
    $initials  = strtoupper(substr($firstName, 0, 1)) . strtoupper(substr($lastName,  0, 1));

    $avatarDisplay = null;
    if ($user->avatar) {
        $all = collect(\App\Livewire\ProfileSettings::avatarCollection())->flatten(1);
        $avatarDisplay = $all->firstWhere('id', $user->avatar);
    }

@endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f59e0b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ \App\Services\ConfigurationService::getBusinessName() }}">

    <title>{{ \App\Services\ConfigurationService::getBusinessName() }}</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- Leaflet CDN --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" data-navigate-once></script>

            {{-- [FIX] x-cloak must be defined here or Alpine elements will flash on load --}}
    <style>[x-cloak] { display: none !important; }</style>

    @vite(['resources/js/app.js'])

    {{-- Form validation --}}
    <script src="{{ asset('js/form-validation.js') }}?v={{ filemtime(public_path('js/form-validation.js')) }}" defer data-navigate-once></script>

    {{-- Alpine Components --}}
    <script src="{{ asset('js/alpine-components.js') }}?v={{ filemtime(public_path('js/alpine-components.js')) }}" data-navigate-once></script>

    {{-- Navigation Concurrency & Abort Guard --}}
    <script src="{{ asset('js/navigation-guard.js') }}?v={{ filemtime(public_path('js/navigation-guard.js')) }}" data-navigate-once></script>

    {{-- SortableJS (for POS Terminal & Ordering drag-and-drop) --}}
    <script src="{{ asset('js/Sortable.min.js') }}" data-navigate-once></script>
    {{-- Leaflet Overrides & Livewire Progress Bar --}}
    <style>
        :root {
            --livewire-progress-bar-color: #e11d48;
        }
        #nprogress .bar {
            height: 2.5px !important;
            background: linear-gradient(90deg, #e11d48, #f43f5e, #fb7185) !important;
            box-shadow: 0 0 10px rgba(225, 29, 72, 0.7) !important;
            z-index: 9999999 !important;
        }
        .leaflet-pane,
        .leaflet-top,
        .leaflet-bottom { z-index: 10 !important; }
        .leaflet-control-layers-toggle {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23374151' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'/%3E%3C/svg%3E") !important;
            background-size: 20px 20px !important;
            background-position: center !important;
        }
    </style>
</head>

<body class="font-sans antialiased text-gray-900 bg-gray-100 transition-colors duration-300 {{ $roleClass }}">

{{-- Global Floating Island Offline Alert (Direct child of body to guarantee no clipping) --}}
<div class="fixed top-4 inset-x-0 z-[9999] flex justify-center pointer-events-none px-4" style="z-index: 999999 !important;">
    <div
        x-data="{
            offline: !navigator.onLine,
            init() {
                window.addEventListener('online', () => this.offline = false);
                window.addEventListener('offline', () => this.offline = true);
                window.addEventListener('network-status', (e) => {
                    this.offline = (e.detail.status === 'offline');
                });
            }
        }"
        x-show="offline"
        x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="-translate-y-10 opacity-0 scale-95"
        x-transition:enter-end="translate-y-0 opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-y-0 opacity-100 scale-100"
        x-transition:leave-end="-translate-y-10 opacity-0 scale-95"
        class="pointer-events-auto flex items-center gap-3 px-5 py-2.5 rounded-full text-white shadow-2xl select-none"
        style="background-color: #e11d48 !important; color: #ffffff !important; border: 1.5px solid #fb7185 !important; box-shadow: 0 20px 30px -10px rgba(225, 29, 72, 0.6), 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;"
    >
        <span class="relative flex h-2.5 w-2.5 shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-200 opacity-80"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
        </span>
        <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-2.828-6.364m0 0L3 3m6.364 6.364a5 5 0 01-1.414 3.536m0 0l2.828 2.828" />
        </svg>
        <span class="text-xs font-bold tracking-wide">No Internet Connection. Actions will not save.</span>
    </div>
</div>

<div
    x-data="{
        sidebarOpen: localStorage.getItem('sidebarOpen') ? JSON.parse(localStorage.getItem('sidebarOpen')) : (window.innerWidth >= 1024),
        isMobile: window.innerWidth < 1024,
        userMenuOpen: false,
        hideOperationalModules: {{ ($user?->hide_modules || ($user?->isSuperAdmin() && !\App\Services\BranchContext::getActiveBranchId())) ? 'true' : 'false' }},
        isSuperAdmin: {{ $user?->isSuperAdmin() ? 'true' : 'false' }},
        init() {
            // Watch sidebarOpen and save to localStorage
            this.$watch('sidebarOpen', (value) => {
                localStorage.setItem('sidebarOpen', JSON.stringify(value));
            });
            window.addEventListener('resize', () => {
                const wasMobile = this.isMobile;
                this.isMobile = window.innerWidth < 1024;
                if (wasMobile && !this.isMobile) {
                    this.sidebarOpen = true; // Auto-open when expanding to desktop
                } else if (!wasMobile && this.isMobile) {
                    this.sidebarOpen = false; // Auto-close when shrinking to mobile
                }
            });
        }
    }"
    @accessibility-config-updated.window="hideOperationalModules = $event.detail.hide_modules"
    class="flex h-screen overflow-hidden w-full"
>

    {{-- ═══════════════════════ MOBILE OVERLAY ═══════════════════════ --}}
    <div x-show="isMobile && sidebarOpen" 
         x-transition.opacity.duration.300ms
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-gray-900/60 z-[105] backdrop-blur-sm lg:hidden"
         x-cloak>
    </div>

    {{-- ═══════════════════════ SIDEBAR ═══════════════════════ --}}
    @livewire('navigation-sidebar')

    {{-- ═══════════════════════ MAIN AREA ═══════════════════════ --}}
    <div class="flex-1 flex flex-col h-full bg-[#F9FAFB] overflow-hidden transition-colors duration-300">

    <header id="top-bar" class="bg-white border-b border-gray-200 sticky top-0 z-[100] flex items-center justify-between px-3 sm:px-6 h-[58px] sm:h-[65px] min-h-[58px] sm:min-h-[65px]">
    <div class="flex items-center gap-4">
        {{-- Hamburger Menu for toggling sidebar --}}
        <button @click="sidebarOpen = !sidebarOpen"
            class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 hover:text-gray-700 focus:outline-none transition-colors">
            <span class="sr-only">Toggle sidebar</span>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        
        @livewire('branch-context-label', ['iconColor' => $iconColor])
        
        {{-- Page Title placeholder/brand (mobile only) --}}
        <div class="flex items-center md:hidden">
            <span class="font-semibold text-[14px] text-gray-900">{{ \App\Services\ConfigurationService::getBusinessName() }}</span>
        </div>
    </div>

    <div class="flex items-center gap-1 sm:gap-3">
        {{-- Clock --}}
        <div wire:ignore wire:key="topbar-clock" x-data="{ 
                time: '',
                date: '',
                _int: null,
                updateTime() {
                    const now = new Date();
                    this.time = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                    this.date = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
                },
                init() {
                    this.updateTime();
                    this._int = setInterval(() => this.updateTime(), 1000);
                },
                destroy() {
                    clearInterval(this._int);
                }
            }" 
            x-init="init()" 
            class="hidden lg:flex items-center gap-4 mr-2">
            <div class="text-right whitespace-nowrap">
                <p x-text="time" class="text-[13px] font-black text-gray-900 leading-none tabular-nums"></p>
                <p x-text="date" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1.5"></p>
            </div>
            <div class="h-8 w-[1px] bg-gray-100 hidden xl:block"></div>
        </div>

        {{-- Professional 4-Bar Signal Telemetry Widget (Matches Clock Line Height) --}}
        <div wire:ignore wire:key="topbar-telemetry-widget" x-data="{
                ping: null,
                bars: 4, // 0 to 4
                status: 'online', // 'online', 'fair', 'slow', 'offline'
                popoverOpen: false,
                networkType: 'WiFi/LAN',
                downlink: null,
                isChecking: false,

                get activeBarFill() {
                    if (this.status === 'offline') return '#E5E7EB';
                    if (this.ping === null) return '#D1D5DB';
                    if (this.bars >= 3) return '#10B981'; // emerald-500
                    if (this.bars === 2) return '#F59E0B'; // amber-500
                    return '#F97316'; // orange-500
                },

                get textClass() {
                    if (this.status === 'offline') return 'text-rose-600';
                    if (this.ping === null) return 'text-gray-400';
                    if (this.bars >= 3) return 'text-gray-900';
                    if (this.bars === 2) return 'text-amber-600';
                    return 'text-orange-600';
                },

                get subTextClass() {
                    if (this.status === 'offline') return 'text-rose-500';
                    if (this.ping === null) return 'text-gray-300';
                    if (this.bars >= 3) return 'text-gray-400';
                    if (this.bars === 2) return 'text-amber-500';
                    return 'text-orange-500';
                },

                calculateBars(ms) {
                    if (ms === null || !navigator.onLine) {
                        this.bars = 0;
                        this.status = 'offline';
                        window.dispatchEvent(new CustomEvent('network-status', { detail: { status: 'offline' } }));
                        return;
                    }
                    if (ms < 150) {
                        this.bars = 4;
                        this.status = 'online';
                    } else if (ms < 300) {
                        this.bars = 3;
                        this.status = 'online';
                    } else if (ms < 500) {
                        this.bars = 2;
                        this.status = 'fair';
                    } else {
                        this.bars = 1;
                        this.status = 'slow';
                    }
                    window.dispatchEvent(new CustomEvent('network-status', { detail: { status: this.status } }));
                },

                detectNetworkType() {
                    const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                    if (conn) {
                        if (conn.effectiveType) {
                            this.networkType = conn.effectiveType.toUpperCase();
                        }
                        if (conn.downlink) {
                            this.downlink = conn.downlink + ' Mbps';
                        }
                    }
                },

                async checkPing() {
                    if (this.isChecking) return;
                    if (!navigator.onLine) {
                        this.ping = null;
                        this.calculateBars(null);
                        return;
                    }

                    this.isChecking = true;
                    const start = performance.now();
                    try {
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 2500);
                        const pingUrl = (window.location.origin || '') + '/ping.txt?t=' + Date.now();
                        const response = await fetch(pingUrl, {
                            method: 'GET',
                            cache: 'no-store',
                            signal: controller.signal
                        });
                        clearTimeout(timeoutId);
                        if (!response.ok) throw new Error('Ping failed');
                        const duration = Math.max(4, Math.round(performance.now() - start));
                        this.ping = duration;
                        this.calculateBars(duration);
                    } catch (e) {
                        if (!navigator.onLine) {
                            this.ping = null;
                            this.calculateBars(null);
                        }
                    } finally {
                        this.isChecking = false;
                    }
                },

                observeRealTraffic() {
                    if (!('PerformanceObserver' in window)) return;

                    const obs = new PerformanceObserver((list) => {
                        for (const entry of list.getEntries()) {
                            if (entry.initiatorType !== 'fetch' && entry.initiatorType !== 'xmlhttprequest') continue;
                            if (!navigator.onLine) continue;

                            const duration = Math.max(1, Math.round(entry.duration));
                            this.ping = duration;
                            this.calculateBars(duration);
                        }
                    });

                    obs.observe({ type: 'resource', buffered: true });
                    window.__mtcTrafficObserver = obs;
                },

                init() {
                    this.detectNetworkType();
                    this.observeRealTraffic();

                    const isReInit = !!window.__mtcPingInterval;
                    if (window.__mtcPingInterval) {
                        clearInterval(window.__mtcPingInterval);
                        window.__mtcPingInterval = null;
                    }

                    if (!isReInit) {
                        this.checkPing();
                    }

                    window.__mtcPingInterval = setInterval(() => {
                        if (!document.hidden && navigator.onLine) {
                            this.checkPing();
                        }
                    }, 5000);

                    document.addEventListener('livewire:navigated', () => {
                        if (navigator.onLine) this.checkPing();
                    });

                    window.addEventListener('online', () => this.checkPing());
                    window.addEventListener('offline', () => {
                        this.ping = null;
                        this.calculateBars(null);
                    });
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden && navigator.onLine) this.checkPing();
                    });

                },

                destroy() {
                    if (window.__mtcPingInterval) {
                        clearInterval(window.__mtcPingInterval);
                        window.__mtcPingInterval = null;
                    }
                    if (window.__mtcTrafficObserver) {
                        window.__mtcTrafficObserver.disconnect();
                        window.__mtcTrafficObserver = null;
                    }
                }
            }"
            @click.outside="popoverOpen = false"
            class="relative flex items-center gap-2 mr-2"
        >
            {{-- Telemetry Button: Bars on the LEFT, Number & MS on the RIGHT (aligned left) --}}
            <button
                type="button"
                @click="popoverOpen = !popoverOpen"
                class="flex items-center gap-1.5 focus:outline-none transition-transform hover:scale-105 cursor-pointer select-none group"
                :title="status === 'offline' ? 'Offline - No Connection' : `Latency: ${ping}ms (${status.toUpperCase()})`"
            >
                {{-- Left: 4 Ascending Signal Bars matching the exact vertical height of the clock (26px) --}}
                <svg class="w-[18px] h-[26px] shrink-0" viewBox="0 0 18 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    {{-- Bar 1 (Lowest) --}}
                    <rect x="0.5" y="17" width="3.2" height="7" rx="1"
                        :fill="bars >= 1 ? activeBarFill : '#E5E7EB'" class="transition-colors duration-200" />
                    {{-- Bar 2 --}}
                    <rect x="5" y="12" width="3.2" height="12" rx="1"
                        :fill="bars >= 2 ? activeBarFill : '#E5E7EB'" class="transition-colors duration-200" />
                    {{-- Bar 3 --}}
                    <rect x="9.5" y="6" width="3.2" height="18" rx="1"
                        :fill="bars >= 3 ? activeBarFill : '#E5E7EB'" class="transition-colors duration-200" />
                    {{-- Bar 4 (Highest, 24px) --}}
                    <rect x="14" y="0" width="3.2" height="24" rx="1"
                        :fill="bars >= 4 ? activeBarFill : '#E5E7EB'" class="transition-colors duration-200" />
                </svg>

                {{-- Right: Text column (Number on top, MS on bottom, aligned LEFT) --}}
                <div class="text-left whitespace-nowrap">
                    <p class="text-[13px] font-black leading-none tabular-nums tracking-tight"
                       :class="textClass"
                       x-text="status === 'offline' ? '---' : (ping !== null ? ping : '---')"></p>
                    <p class="text-[10px] font-bold uppercase tracking-widest mt-1.5 leading-none"
                       :class="subTextClass"
                       x-text="status === 'offline' ? 'OFFLINE' : 'MS'"></p>
                </div>
            </button>

            {{-- Divider matching the clock divider --}}
            <div class="h-8 w-[1px] bg-gray-100 hidden sm:block"></div>

            {{-- Popover Card --}}
            <div
                x-show="popoverOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-150 transform"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-[0_10px_35px_rgba(0,0,0,0.08)] p-3 z-[110] text-gray-700 select-none"
            >
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Network Health</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                        :class="{
                            'bg-emerald-100 text-emerald-800': status === 'online',
                            'bg-amber-100 text-amber-800': status === 'fair',
                            'bg-orange-100 text-orange-800': status === 'slow',
                            'bg-rose-100 text-rose-800': status === 'offline'
                        }"
                        x-text="status === 'online' ? 'Optimal' : (status === 'fair' ? 'Moderate' : (status === 'slow' ? 'High Latency' : 'Disconnected'))"
                    ></span>
                </div>
                
                <div class="space-y-1.5 pt-2 text-[11px]">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Roundtrip Latency:</span>
                        <span class="font-bold tabular-nums" x-text="ping ? ping + ' ms' : 'N/A'"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Connection Speed:</span>
                        <span class="font-bold" x-text="networkType"></span>
                    </div>
                    <div class="flex justify-between items-center" x-show="downlink">
                        <span class="text-gray-500">Est. Bandwidth:</span>
                        <span class="font-bold tabular-nums" x-text="downlink"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Signal Strength:</span>
                        <span class="font-bold" :class="activeBarFill === '#10B981' ? 'text-emerald-600' : 'text-amber-600'" x-text="bars + ' / 4 Bars'"></span>
                    </div>
                </div>

                <div class="mt-2.5 pt-2 border-t border-gray-100 text-[10px] text-gray-400">
                    <span x-show="status === 'online'">⚡ Network speed is optimal. Actions save instantly.</span>
                    <span x-show="status === 'fair'">⏱️ Normal response time.</span>
                    <span x-show="status === 'slow'">⚠️ High network delay. Requests may take longer.</span>
                    <span x-show="status === 'offline'">🔴 Internet dropped. Reconnecting...</span>
                </div>
            </div>
        </div>

        @livewire('topbar-notifications')

        {{-- Profile Dropdown --}}
        <div class="relative" x-data="{ 
            profileMenuOpen: false,
            avatarEmoji: @js($avatarDisplay['emoji'] ?? null),
            avatarStyle: @js($avatarDisplay['style'] ?? null)
        }" 
        @branch-switched.window="profileMenuOpen = false"
        @avatar-updated.window="avatarEmoji = $event.detail.emoji; avatarStyle = $event.detail.style"
        >
            <button @click="profileMenuOpen = !profileMenuOpen" @click.outside="profileMenuOpen = false" class="flex items-center gap-2.5 focus:outline-none transition-transform hover:scale-105">
                <div class="text-right hidden sm:block">
                    <p class="text-[13px] font-semibold text-gray-900 leading-tight">{{ $firstName }} {{ $lastName }}</p>
                    <p class="text-[11px] text-gray-500 leading-tight">{{ $roleName }}</p>
                </div>
                
                <template x-if="avatarEmoji">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-lg shadow-sm ring-2 ring-white" :style="avatarStyle" x-text="avatarEmoji"></div>
                </template>
                <template x-if="!avatarEmoji">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-900 text-white font-bold text-[11px] ring-2 ring-white shadow-sm">
                        {{ $initials }}
                    </div>
                </template>
            </button>
            <div x-show="profileMenuOpen" x-transition x-cloak
                class="absolute right-0 mt-2 w-52 bg-white border border-gray-100 rounded-xl shadow-[0_4px_25px_rgba(0,0,0,0.1)] py-1 z-50 overflow-hidden">
                <div class="px-4 py-2 border-b border-gray-100 bg-gray-50/50">
                    <p class="text-[12px] font-bold text-gray-900">{{ $firstName }} {{ $lastName }}</p>
                    <p class="text-[11px] text-gray-500 truncate">{{ $email }}</p>
                </div>
                <x-dropdown-link :href="route('profile.edit')" navigate="true">Profile Settings</x-dropdown-link>
                <div class="border-t border-gray-50 mt-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="text-red-500 hover:bg-red-50 focus:bg-red-50 font-medium">
                            Log Out
                        </x-dropdown-link>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

        {{-- [FIX] isset($noPadding) && $noPadding → $noPadding ?? false (cleaner null-safe check) --}}
        @if($noPadding ?? false)
            <main class="flex-1 relative flex flex-col" style="min-height:0;">
                {{-- Branch-switch overlay --}}
                <div
                    x-data="{ show: false }"
                    x-on:branch-switched.window="show = true; setTimeout(() => show = false, 600)"
                    x-show="show"
                    x-cloak
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 z-[50] bg-[#F9FAFB]/60 pointer-events-none"
                ></div>

                <div class="flex-1 flex flex-col min-h-full overflow-x-hidden overflow-y-auto">
                    {{ $slot ?? '' }}
                    @yield('content')
                </div>
            </main>
        @else
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-3 sm:p-4 lg:p-6" style="min-height:0;">
                <div class="max-w-[1400px] mx-auto min-h-full">
                    <div class="bg-white rounded-xl sm:rounded-2xl border border-gray-200 p-3 sm:p-4 lg:p-6 shadow-[0_4px_30px_rgba(0,0,0,0.02)]">
                        {{ $slot ?? '' }}
                        @yield('content')
                    </div>
                </div>
            </main>
        @endif

    </div>
</div>

@persist('toast-container')
<x-toast />
@endpersist

{{-- Global helper scripts that must be available before Livewire/Alpine initialize. --}}
@stack('beforeLivewireScripts')

{{-- [FIX] @livewireScripts moved from <head> to end of <body> so it runs after the DOM is ready --}}
    {{-- Global Modals --}}

    @livewireScripts

@stack('scripts')

</body>
</html>

