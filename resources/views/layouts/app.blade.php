<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
@php
    $user      = auth()->user();
    $roleTheme = $user?->getRoleTheme() ?? [];
    $roleClass = 'role-' . strtolower($roleTheme['primary'] ?? 'guest');

    // Topbar data
    $branch = \App\Models\Branch::find(\App\Services\BranchContext::getActiveBranchId() ?: ($user?->branch_id));

    $contextLabel = $user->isSuperAdmin() ? 'Global Context' : 'Assigned Branch';
    $contextValue = $branch?->branch_name ?? ($user->isSuperAdmin() ? 'General Headquarters' : 'No Branch Assigned');

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
    <script src="{{ asset('js/form-validation.js') }}" defer data-navigate-once></script>

    {{-- Alpine Components --}}
    <script src="{{ asset('js/alpine-components.js') }}" defer data-navigate-once></script>
    {{-- Leaflet Overrides --}}
    <style>
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

<div
    x-data="{
        sidebarOpen: localStorage.getItem('sidebarOpen') ? JSON.parse(localStorage.getItem('sidebarOpen')) : (window.innerWidth >= 1024),
        isMobile: window.innerWidth < 1024,
        userMenuOpen: false,
        hideOperationalModules: {{ $user?->hide_modules ? 'true' : 'false' }},
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
        
        <div class="hidden md:flex items-center gap-2.5 px-3 py-1.5 bg-gray-50 border border-gray-100 rounded-xl transition-all shadow-sm ml-2"
             x-data="{ 
                contextLabel: @js($contextLabel),
                contextValue: @js($contextValue)
             }"
             @branch-switched.window="if($event.detail.branchName) contextValue = $event.detail.branchName"
             @context-updated.window="if($event.detail.branchName) contextValue = $event.detail.branchName"
        >
            <svg class="w-4 h-4 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="text-[12px] font-bold text-gray-500 uppercase tracking-tight truncate max-w-[200px] lg:max-w-none">
                <span x-text="contextLabel"></span>: <span class="text-gray-900 font-black tracking-normal" x-text="contextValue"></span>
            </span>
        </div>
        
        {{-- Page Title placeholder/brand (mobile only) --}}
        <div class="flex items-center md:hidden">
            <span class="font-semibold text-[14px] text-gray-900">{{ \App\Services\ConfigurationService::getBusinessName() }}</span>
        </div>
    </div>

    <div class="flex items-center gap-1 sm:gap-3">
        {{-- Clock --}}
        <div x-data="{ 
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

<x-toast />

{{-- Global helper scripts that must be available before Livewire/Alpine initialize. --}}
@stack('beforeLivewireScripts')

{{-- [FIX] @livewireScripts moved from <head> to end of <body> so it runs after the DOM is ready --}}
    {{-- Global Modals --}}

    @livewireScripts

@stack('scripts')

</body>
</html>

