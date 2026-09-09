<div>
<aside
    id="main-sidebar"
    x-ref="sidebar"
    :class="[
        isMobile ? 'fixed inset-y-0 left-0 z-[110] transform w-[280px] h-screen' : 'relative z-30 flex-shrink-0 h-full',
        isMobile ? (sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full') : (sidebarOpen ? 'w-[280px]' : 'w-16')
    ]"
    class="bg-white border-r border-gray-200 shadow-sm flex flex-col transition-all duration-300 ease-in-out min-h-0"
>

    {{-- ── Branding ── --}}
    @php
        $initialLogoUrl      = \App\Services\ConfigurationService::getBusinessLogoUrl();
        $initialBusinessName = \App\Services\ConfigurationService::getBusinessName();
        $user = auth()->user();
    @endphp

    <div
        class="h-[65px] min-h-[65px] flex items-center border-b border-gray-200 transition-all duration-300 relative"
        :class="sidebarOpen ? 'px-5' : 'px-0 justify-center'"
        x-data="{ logoUrl: @js($initialLogoUrl), bName: @js($initialBusinessName) }"
        @businessconfigupdated.window="logoUrl = $event.detail.logo_url; bName = $event.detail.business_name"
    >
        <div class="flex items-center gap-3.5 whitespace-nowrap">
            <template x-if="logoUrl">
                <img :src="logoUrl" alt="Logo"
                    class="w-[34px] h-[34px] rounded-[10px] shrink-0 object-cover shadow-sm transition-all duration-300">
            </template>
            <template x-if="!logoUrl">
                <div class="w-[34px] h-[34px] rounded-[10px] shrink-0 bg-black text-white flex items-center justify-center font-bold text-[10px] shadow-sm">
                    MTC
                </div>
            </template>
            <span
                class="font-bold tracking-tight text-gray-900 text-[15px] transition-opacity duration-300"
                x-text="bName"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'"
            ></span>
        </div>

        {{-- Mobile Close Button --}}
        <button 
            x-show="isMobile && sidebarOpen"
            @click="sidebarOpen = false"
            class="absolute right-4 p-2 text-gray-400 hover:text-gray-600 lg:hidden"
            x-cloak
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- ── Navigation ── --}}
    <nav @click="if(isMobile && $event.target.closest('a')) sidebarOpen = false"
        x-data="{
            hideOps: @js($this->effectiveHideOperationalModules),
            isSubBranch: @js($this->isSubBranch),
            showOrderInbox: @js($this->showOrderInbox),
        }"
        @accessibility-config-updated.window="
            hideOps = $event.detail.hide_modules;
            if ($event.detail.has_branch !== undefined) {
                isSubBranch = !$event.detail.hide_modules && Boolean($event.detail.is_sub);
                showOrderInbox = !$event.detail.hide_modules && Boolean($event.detail.is_main);
            }
        "
        @branch-switched.window="
            if ($event.detail.hasBranch !== undefined) {
                isSubBranch = !hideOps && Boolean($event.detail.hasBranch) && !Boolean($event.detail.isMain);
                showOrderInbox = !hideOps && Boolean($event.detail.hasBranch) && Boolean($event.detail.isMain);
            }
        "
        @branchContextUpdated.window="
            if ($event.detail.hasBranch !== undefined) {
                isSubBranch = !hideOps && Boolean($event.detail.hasBranch) && !Boolean($event.detail.isMain);
                showOrderInbox = !hideOps && Boolean($event.detail.hasBranch) && Boolean($event.detail.isMain);
            }
        "
        class="flex-1 overflow-y-auto overflow-x-hidden px-3 pb-4 pt-4 text-[13px] font-medium text-gray-600">

        @if($user && !$user->isRider())

            {{-- ── I. OVERVIEW (Daily Operations) ── --}}
            <div class="mb-5">
                <h3 class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider transition-opacity duration-300"
                    x-show="!hideOps"
                    :class="sidebarOpen ? 'opacity-100' : 'opacity-0 h-0 overflow-hidden hidden'">
                    Overview
                </h3>

                <a href="{{ route('dashboard') }}" wire:navigate
                    class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                        {{ request()->routeIs('dashboard') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="transition-opacity duration-300 whitespace-nowrap"
                        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Dashboard</span>
                </a>

                <div x-show="!hideOps" x-transition.opacity x-cloak>
                    <a href="{{ route('pos.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('pos.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">POS Terminal</span>
                    </a>

                    <a href="{{ route('orders.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('orders.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Order Management</span>
                    </a>

                    <a href="{{ route('kds.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('kds.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Kitchen Display</span>
                    </a>
                </div>
            </div>

            {{-- ── II. RESOURCES (Menu & Inventory) ── --}}
            <div class="mb-5">
                <h3 class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider transition-opacity duration-300"
                    :class="sidebarOpen ? 'opacity-100' : 'opacity-0 h-0 overflow-hidden hidden'">
                    Resources
                </h3>

                @if($user->role_id <= 2)
                    <a href="{{ route('menu.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('menu.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Menu Management</span>
                    </a>

                    @if($user->isSuperAdmin())
                        <a href="{{ route('categories.index') }}" wire:navigate
                            class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                                {{ request()->routeIs('categories.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span class="transition-opacity duration-300 whitespace-nowrap"
                                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Categories Catalog</span>
                        </a>

                        <a href="{{ route('library.index') }}" wire:navigate
                            class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                                {{ request()->routeIs('library.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="transition-opacity duration-300 whitespace-nowrap"
                                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Options Library</span>
                        </a>
                    @endif
                @endif

                {{-- Inventory & Ingredients (role ≤ 3) --}}
                @if($user->role_id <= 3)
                    @php
                        $isStockPage      = request()->routeIs('stock.index');
                        $isAdjustmentPage = request()->routeIs('stock.adjustment');
                        $isOrderingPage   = request()->routeIs('stock.orders') || request()->routeIs('stock.orders.admin');
                        $stockActive      = $isStockPage || $isAdjustmentPage || $isOrderingPage;
                    @endphp

                    @if($user->role_id <= 2)
                        {{-- Staff Manager+: full expandable section --}}
                        <div x-data="{ stockOpen: {{ $stockActive ? 'true' : 'false' }} }">
                            <button type="button" @click="stockOpen = !stockOpen"
                                class="w-full flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                                    {{ $stockActive ? 'bg-[#F3F4F6] text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span class="flex-1 text-left transition-opacity duration-300 whitespace-nowrap"
                                    :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Inventory Management</span>
                                <svg x-show="sidebarOpen" class="w-3 h-3 shrink-0 text-gray-400 transition-transform duration-200" :class="stockOpen ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="stockOpen && sidebarOpen" x-cloak x-transition.opacity
                                class="mt-0.5 ml-7 pl-3 border-l-2 border-gray-100 space-y-0.5">

                                {{-- 1. Inventory Overview --}}
                                <a href="{{ route('stock.index') }}" wire:navigate
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-[12px] font-medium transition-colors
                                        {{ $isStockPage ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m6 10V7M4 21h16a1 1 0 001-1V4a1 1 0 00-1-1H4a1 1 0 00-1 1v16a1 1 0 001 1z" />
                                    </svg>
                                    <span class="whitespace-nowrap">Inventory Overview</span>
                                </a>

                                {{-- 2. Stock Adjustments --}}
                                <a href="{{ route('stock.adjustment') }}" wire:navigate
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-[12px] font-medium transition-colors
                                        {{ $isAdjustmentPage ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span class="whitespace-nowrap">Stock Adjustments</span>
                                </a>

                                {{-- 3. Request Supplies (sub-branch) or Branch Requests (HQ/main) --}}
                                <a href="{{ route('stock.orders') }}" wire:navigate
                                    x-show="!hideOps && isSubBranch"
                                    x-cloak
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-[12px] font-medium transition-colors
                                        {{ request()->routeIs('stock.orders') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="whitespace-nowrap">Request Supplies</span>
                                </a>

                                <a href="{{ route('stock.orders.admin') }}" wire:navigate
                                    x-show="!hideOps && showOrderInbox"
                                    x-cloak
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-[12px] font-medium transition-colors
                                        {{ request()->routeIs('stock.orders.admin') ? 'bg-amber-50 text-amber-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <span class="flex-1 whitespace-nowrap">Branch Requests</span>
                                    @if($this->pending_orders_count > 0)
                                        <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full bg-amber-500 text-[9px] font-black text-white shadow-lg shadow-amber-200/40 animate-pulse">
                                            {{ $this->pending_orders_count }}
                                        </span>
                                    @endif
                                </a>
                            </div>
                        </div>
                    @else
                        {{-- Staff (role 3): plain link, no submenu --}}
                        <a href="{{ route('stock.index') }}" wire:navigate
                            class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                                {{ $isStockPage ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span class="transition-opacity duration-300 whitespace-nowrap"
                                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Stock &amp; Ingredients</span>
                        </a>
                    @endif
                @endif
            </div>

            {{-- ── III. ADMINISTRATION (Organization) ── --}}
            @if($user->role_id <= 2)
            <div class="mb-5">
                <h3 class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider transition-opacity duration-300"
                    :class="sidebarOpen ? 'opacity-100' : 'opacity-0 h-0 overflow-hidden hidden'">
                    Administration
                </h3>
                    <a href="{{ route('branches.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('branches.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Branch Locations</span>
                    </a>

                    <a href="{{ route('users.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('users.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">
                            {{ $user->isSuperAdmin() ? 'User Management' : 'Staff Management' }}
                        </span>
                    </a>

                    <a href="{{ route('customers.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('customers.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.54 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Customer Feedbacks</span>
                    </a>
            </div>
            @endif

            {{-- ── IV. SYSTEM (Configuration) ── --}}
            @if($user->isSuperAdmin() || $user->isAdmin())
                <div class="mb-4">
                    <h3 class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider transition-opacity duration-300"
                        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 h-0 overflow-hidden hidden'">
                        System
                    </h3>

                    <a href="{{ route('reports.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('reports.*') || request()->routeIs('intelligence.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Business Reports</span>
                    </a>

                    <a href="{{ route('settings.index') }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-1.5 mt-0.5 rounded-lg transition-colors
                            {{ request()->routeIs('settings.*') ? 'bg-[#F3F4F6] text-gray-900' : 'hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="transition-opacity duration-300 whitespace-nowrap"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">System Administration</span>
                    </a>
                </div>
            @endif

        @else

            {{-- ── Rider: restricted view ── --}}
            <div class="px-3 py-6 text-center">
                <div class="w-12 h-12 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-xs font-semibold text-gray-900 mb-1" :class="sidebarOpen ? '' : 'hidden'">Mobile App Access</p>
                <p class="text-[10px] text-gray-500 leading-relaxed" :class="sidebarOpen ? '' : 'hidden'">
                    Please use the Rider mobile app to manage your deliveries.
                </p>

                <a href="{{ route('profile.edit') }}" wire:navigate
                    class="flex items-center gap-3 px-3 py-1.5 mt-6 rounded-lg transition-colors hover:bg-gray-50 hover:text-gray-900">
                    <svg class="w-[18px] h-[18px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="transition-opacity duration-300 whitespace-nowrap"
                        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Account Settings</span>
                </a>
            </div>

        @endif
    </nav>
</aside>
</div>
