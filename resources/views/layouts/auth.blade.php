<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f59e0b">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Laravel') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/js/app.js'])

        {{-- Must be byte-identical to the tag in app.blade.php so Livewire treats it as already loaded --}}
        <script src="{{ asset('js/navigation-guard.js') }}?v={{ filemtime(public_path('js/navigation-guard.js')) }}" data-navigate-once></script>

        @livewireStyles

        <style>

            
            /* Custom blurred glow behind the container */
            .container-glow {
                position: absolute;
                inset: -20px;
                background: inherit;
                filter: blur(40px);
                z-index: -1;
                opacity: 0.5;
            }
            
             @keyframes fadeSlideForm {
                from { opacity: 0; transform: translateX(10px); }
                to { opacity: 1; transform: translateX(0); }
            }
            .animate-form-enter { animation: fadeSlideForm 0.3s cubic-bezier(0.4, 0, 0.2, 1) both; }

            /* Split panels slide cleanly off-canvas to left and right */
            @keyframes authSlideLeft {
                0% {
                    transform: translateX(0);
                    opacity: 1;
                }
                60% {
                    opacity: 1;
                }
                100% {
                    transform: translateX(calc(-50vw - 320px));
                    opacity: 0;
                }
            }
            @keyframes authSlideRight {
                0% {
                    transform: translateX(0);
                    opacity: 1;
                }
                60% {
                    opacity: 1;
                }
                100% {
                    transform: translateX(calc(50vw + 320px));
                    opacity: 0;
                }
            }
            .auth-anim-left {
                animation: authSlideLeft 0.75s cubic-bezier(0.6, 0.05, 0.2, 1) forwards !important;
            }
            .auth-anim-right {
                animation: authSlideRight 0.75s cubic-bezier(0.6, 0.05, 0.2, 1) forwards !important;
            }

            /* Container & Panel Geometry (Eliminates all border lines and unifies the two halves into one card) */
            .auth-card-container {
                position: relative;
                width: 100%;
                max-width: 1000px;
                min-height: 560px;
                display: flex;
                flex-direction: column;
                z-index: 10;
                background: transparent !important;
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
            }
            @media (min-width: 768px) {
                .auth-card-container {
                    flex-direction: row;
                    height: 600px;
                }
            }

            .auth-panel-left {
                width: 100%;
                border-radius: 24px;
                overflow: hidden;
                border: none !important;
                outline: none !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
            @media (min-width: 768px) {
                .auth-panel-left {
                    width: 50%;
                    border-top-right-radius: 0 !important;
                    border-bottom-right-radius: 0 !important;
                    border-top-left-radius: 24px !important;
                    border-bottom-left-radius: 24px !important;
                }
            }

            .auth-panel-right {
                width: 100%;
                background-color: #ffffff;
                border-radius: 24px;
                overflow: hidden;
                border: none !important;
                outline: none !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
            @media (min-width: 768px) {
                .auth-panel-right {
                    width: 50%;
                    border-top-left-radius: 0 !important;
                    border-bottom-left-radius: 0 !important;
                    border-top-right-radius: 24px !important;
                    border-bottom-right-radius: 24px !important;
                }
            }

            /* Full-screen catch overlay: softly fades to solid dark before swapping to dashboard */
            .auth-catch-overlay {
                position: fixed;
                inset: 0;
                background-color: #0c0a09;
                z-index: 50;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.3s ease-out;
            }
            .auth-catch-overlay.is-active {
                opacity: 1;
                transition-delay: 0.45s;
            }

        /* Completely suppress NProgress bar on auth — the button spinner already indicates loading */
        #nprogress,
        #nprogress .bar,
        .nprogress-custom-parent #nprogress {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        </style>
    </head>
                <body x-data="{ splitting: false }" x-on:auth-split.window="splitting = true" x-on:login-transition-failed.window="splitting = false" class="font-sans text-gray-900 antialiased bg-stone-950 overflow-x-hidden overflow-y-auto min-h-screen flex items-center justify-center p-4 sm:p-8 relative">
        
        <!-- Full-page Takoyaki Background Image -->
        <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
            <img src="{{ asset('images/login-bg.jpg') }}" alt="{{ \App\Services\ConfigurationService::getBusinessName() }}" class="w-full h-full object-cover object-center transform transition-transform duration-1000 ease-out" :class="splitting ? 'scale-100' : 'scale-105'" />
            <!-- Softens and clears blur when columns split so the photo is fully visible -->
            <div class="absolute inset-0 transition-all duration-700 ease-out" :class="splitting ? 'bg-black/30 backdrop-blur-none' : 'bg-stone-950/65 backdrop-blur-[2px]'"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-stone-950/80 via-transparent to-stone-950/60 transition-opacity duration-700" :class="splitting ? 'opacity-30' : 'opacity-100'"></div>
        </div>

        <!-- Full-screen catch overlay: softly fades to solid dark before swapping to dashboard -->
        <div class="auth-catch-overlay" :class="splitting ? 'is-active' : ''"></div>

        <!-- Centered Container — zero borders, transparent background, seamless unified card -->
        <div class="auth-card-container my-4 sm:my-0">
            
            <div class="container-glow bg-amber-500/10 transition-opacity duration-500" :class="splitting ? 'opacity-0' : 'opacity-100'"></div>

            <!-- Left Side: Marketing Cover -->
            <div :class="splitting ? 'auth-anim-left' : ''"
                 class="auth-panel-left hidden md:flex relative flex-col justify-between p-10 group bg-stone-900">
                <img src="{{ asset('images/mtc-logo-only.png') }}" alt="{{ \App\Services\ConfigurationService::getBusinessName() }}" class="absolute inset-0 w-full h-full object-cover" />
                <!-- Overlay for readability -->
                <div class="absolute inset-0 bg-black/45"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-orange-100/20 via-transparent to-transparent"></div>
                
                <div class="relative z-10 flex items-center gap-2">
                     <span class="text-[16px] font-extrabold tracking-tight text-white uppercase">{{ \App\Services\ConfigurationService::getBusinessName() }}</span>
                </div>

                <div class="relative z-10 mt-auto">
                    <p class="text-slate-200 text-sm opacity-95 leading-relaxed max-w-sm">Manage your branch monitoring, sales data, and staff execution all in one centralized command center.</p>
                </div>
            </div>

            <!-- Right Side: Form -->
            <div :class="splitting ? 'auth-anim-right' : ''"
                 class="auth-panel-right flex flex-col p-6 sm:p-10 lg:p-12 relative h-full overflow-y-auto no-scrollbar">
                <div class="flex-1 flex flex-col justify-center max-w-[380px] mx-auto w-full animate-form-enter">
                    <!-- Mobile only logo -->
                    <div class="flex items-center gap-2 mb-8 md:hidden justify-center hover:opacity-80 transition-opacity">
                         <x-application-logo class="w-8 h-8 fill-current text-amber-600" />
                         <span class="text-xl font-extrabold tracking-tight text-gray-900 uppercase">{{ \App\Services\ConfigurationService::getBusinessName() }}</span>
                    </div>
                    
                    {{ $slot }}
                </div>
            </div>
            
        </div>
        <x-toast />
        @livewireScripts
    </body>
</html>

