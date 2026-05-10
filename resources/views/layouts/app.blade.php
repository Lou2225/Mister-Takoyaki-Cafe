<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
@php
    $user      = auth()->user();
    $roleTheme = $user?->getRoleTheme() ?? [];
    $roleClass = 'role-' . strtolower($roleTheme['primary'] ?? 'guest');
@endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Mister Takoyaki') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- [FIX] x-cloak must be defined here or Alpine elements will flash on load --}}
    <style>[x-cloak] { display: none !important; }</style>

    {{-- Styles --}}
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ApexCharts --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- Form validation --}}
    <script src="{{ asset('js/form-validation.js') }}"></script>

    {{-- Leaflet: only on branches + users pages --}}
    @if(request()->routeIs('branches.index') || request()->routeIs('users.index') || request()->routeIs('settings.index'))
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <style>
            .leaflet-pane,
            .leaflet-top,
            .leaflet-bottom { z-index: 10 !important; }
        </style>
    @endif
</head>

<body class="font-sans antialiased text-gray-900 bg-gray-100 transition-colors duration-300 {{ $roleClass }}">

<div
    x-data="{
        sidebarOpen: window.innerWidth >= 1024,
        isMobile: window.innerWidth < 1024,
        userMenuOpen: false,
        hideOperationalModules: {{ $user?->hide_modules ? 'true' : 'false' }},
        isSuperAdmin: {{ $user?->isSuperAdmin() ? 'true' : 'false' }},
        init() {
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
         class="fixed inset-0 bg-gray-900/60 z-40 backdrop-blur-sm lg:hidden"
         x-cloak>
    </div>

    {{-- ═══════════════════════ SIDEBAR ═══════════════════════ --}}
    @livewire('navigation-sidebar')

    {{-- ═══════════════════════ MAIN AREA ═══════════════════════ --}}
    <div class="flex-1 flex flex-col h-full bg-[#F9FAFB] overflow-hidden transition-colors duration-300">

        @livewire('topbar')

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
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6" style="min-height:0;">
                <div class="max-w-[1400px] mx-auto min-h-full">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-[0_4px_30px_rgba(0,0,0,0.02)]">
                        {{ $slot ?? '' }}
                        @yield('content')
                    </div>
                </div>
            </main>
        @endif

    </div>
</div>

<x-toast />

{{-- [FIX] @livewireScripts moved from <head> to end of <body> so it runs after the DOM is ready --}}
    {{-- Global Modals --}}
    <x-modal name="notification-history-modal" maxWidth="2xl">
        @livewire('notification-history')
    </x-modal>

    @livewireScripts

@stack('scripts')

</body>
</html>