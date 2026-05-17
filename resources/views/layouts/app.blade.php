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

    // Notifications - simplified, no polling
    $notifications = [];
    $unreadCount = 0;
@endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Mister Takoyaki Cafe') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- [FIX] x-cloak must be defined here or Alpine elements will flash on load --}}
    <style>[x-cloak] { display: none !important; }</style>

    {{-- Styles --}}
    @livewireStyles
    @vite(['resources/js/app.js'])

    {{-- ApexCharts --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts" data-navigate-once></script>

    {{-- Form validation --}}
    <script src="{{ asset('js/form-validation.js') }}" data-navigate-once></script>

    {{-- Alpine Components --}}
    <script src="{{ asset('js/alpine-components.js') }}" data-navigate-once></script>

    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" data-navigate-once></script>
    <style>
        .leaflet-pane,
        .leaflet-top,
        .leaflet-bottom { z-index: 10 !important; }
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
        
        <div class="hidden md:flex items-center gap-2.5 px-3 py-1.5 bg-gray-50 border border-gray-100 rounded-xl transition-all shadow-sm ml-2">
            <svg class="w-4 h-4 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="text-[12px] font-bold text-gray-500 uppercase tracking-tight truncate max-w-[200px] lg:max-w-none">
                {{ $contextLabel }}: <span class="text-gray-900 font-black tracking-normal">{{ $contextValue }}</span>
            </span>
        </div>
        
        {{-- Page Title placeholder/brand (mobile only) --}}
        <div class="flex items-center md:hidden">
            <span class="font-semibold text-[14px] text-gray-900">Mister Takoyaki Cafe</span>
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

        {{-- Notifications --}}
        <div class="relative" x-data="{ notificationsOpen: false }" @close-notifications.window="notificationsOpen = false">
            <button @click="notificationsOpen = !notificationsOpen" @click.outside="notificationsOpen = false" 
                class="p-3 text-gray-400 hover:text-gray-500 rounded-full hover:bg-gray-100 transition-colors focus:outline-none relative group">
                <span class="sr-only">View notifications</span>
                <svg class="h-6 w-6 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                @if($unreadCount > 0)
                    <div class="absolute top-2 right-2 min-w-[18px] h-5 px-1 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center ring-2 ring-white shadow-sm">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </div>
                @endif
            </button>

            <div x-show="notificationsOpen" x-transition x-cloak
                class="absolute right-0 mt-2 w-80 bg-white border border-gray-100 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.2)] z-[100] overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
                    <h3 class="text-[12px] font-black text-gray-900 uppercase tracking-widest">System Alerts</h3>
                    @if($unreadCount > 0)
                        <button class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors">Mark All as Read</button>
                    @endif
                </div>
                <div style="max-height: 320px !important; overflow-y: auto !important;">
                    @forelse($notifications as $n)
                        <div class="relative group/n">
                            <button 
                                class="w-full text-left px-4 py-2.5 hover:bg-gray-50 transition-colors border-b border-gray-50/50 last:border-0 flex gap-3 {{ !$n['is_read'] ? 'bg-indigo-50/10' : '' }}">
                                <div class="w-8 h-8 rounded-lg bg-{{ $n['color'] }}-50 flex items-center justify-center shrink-0">
                                    @if($n['type'] === 'stock')
                                        <svg class="w-4 h-4 text-{{ $n['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    @elseif($n['type'] === 'expiry')
                                        <svg class="w-4 h-4 text-{{ $n['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @elseif($n['type'] === 'order')
                                        <svg class="w-4 h-4 text-{{ $n['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    @else
                                        <svg class="w-4 h-4 text-{{ $n['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.921-.755 1.688-1.54 1.118l-3.976-2.888a1 1 0 00-1.175 0l-3.976 2.888c-.784.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.383-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 pr-6">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-[11px] font-bold text-gray-900 truncate">{{ $n['title'] }}</p>
                                        <span class="text-[9px] font-medium text-gray-400 whitespace-nowrap uppercase tracking-tighter">{{ $n['time'] }}</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 leading-normal mt-0.5 line-clamp-2">{{ $n['message'] }}</p>
                                </div>
                            </button>
                            @if(!$n['is_read'])
                                <button 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-gray-300 hover:text-indigo-600 transition-all opacity-0 group-hover/n:opacity-100 bg-white rounded-lg shadow-sm border border-gray-100"
                                    title="Mark as read">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v2m4.667 3.333L10.667 15l3.333 3.333m-1.334-1.333h6.667"/></svg>
                            </div>
                            <p class="text-[12px] font-bold text-gray-400">All clear!</p>
                            <p class="text-[10px] text-gray-400 mt-1">You have no new notifications.</p>
                        </div>
                    @endforelse
                </div>
                <a href="{{ route('notifications.index') }}" wire:navigate
                    class="block w-full px-4 py-2 bg-gray-50 text-center text-[10px] font-black text-gray-500 hover:bg-gray-100 hover:text-gray-700 uppercase tracking-[0.2em] transition-all">
                    Notification History
                </a>
            </div>
        </div>

        {{-- Profile Dropdown --}}
        <div class="relative" x-data="{ profileMenuOpen: false }" @branch-switched.window="profileMenuOpen = false">
            <button @click="profileMenuOpen = !profileMenuOpen" @click.outside="profileMenuOpen = false" class="flex items-center gap-2.5 focus:outline-none transition-transform hover:scale-105">
                <div class="text-right hidden sm:block">
                    <p class="text-[13px] font-semibold text-gray-900 leading-tight">{{ $firstName }} {{ $lastName }}</p>
                    <p class="text-[11px] text-gray-500 leading-tight">{{ $roleName }}</p>
                </div>
                @if($avatarDisplay)
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-lg shadow-sm ring-2 ring-white" style="{{ $avatarDisplay['style'] }}">
                        {{ $avatarDisplay['emoji'] }}
                    </div>
                @else
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-900 text-white font-bold text-[11px] ring-2 ring-white shadow-sm">
                        {{ $initials }}
                    </div>
                @endif
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