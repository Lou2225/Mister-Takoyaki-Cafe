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
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    for (var r of registrations) { r.unregister(); }
                });
            }
        </script>
        @vite(['resources/js/app.js'])
        @livewireStyles

        <style>
            .auth-bubbles {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                overflow: hidden;
                z-index: 0;
            }
            .bubble {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.05); /* very subtle for dark background */
                animation: float 20s infinite ease-in-out;
            }
            @keyframes float {
                0%, 100% { transform: translateY(0) translateX(0); }
                50% { transform: translateY(-20px) translateX(20px); }
            }
            
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

                        /* Split panels via @keyframes instead of transitions — the whole
               timeline (position AND opacity) is baked into one declarative
               rule per element, so there's nothing left to race or get
               silently overridden. Each panel stays fully opaque while it
               visibly slides (0%–65%), then fades only in the last stretch
               once it's mostly off-canvas (65%–100%). */
            @keyframes authSlideLeft {
                0%   { transform: translateX(0);        opacity: 1; }
                65%  { transform: translateX(-115%);    opacity: 1; }
                100% { transform: translateX(-115%);    opacity: 0; }
            }
            @keyframes authSlideRight {
                0%   { transform: translateX(0);        opacity: 1; }
                65%  { transform: translateX(115%);     opacity: 1; }
                100% { transform: translateX(115%);     opacity: 0; }
            }
            .auth-anim-left {
                animation: authSlideLeft 0.7s cubic-bezier(0.65, 0, 0.35, 1) forwards;
            }
            .auth-anim-right {
                animation: authSlideRight 0.7s cubic-bezier(0.65, 0, 0.35, 1) forwards;
                animation-delay: 0.05s;
            }
        </style>
    </head>
                <body x-data="{ splitting: false }" x-on:auth-split.window="splitting = true" class="font-sans text-gray-900 antialiased bg-indigo-950 overflow-x-hidden overflow-y-auto min-h-screen flex items-center justify-center p-4 sm:p-8 relative">
        
        <!-- Full-page Bubble Background -->
        <div class="auth-bubbles pointer-events-none fixed inset-0">
            <div class="bubble w-64 h-64 -top-20 -left-20" style="animation-delay: 0s;"></div>
            <div class="bubble w-96 h-96 top-1/4 -right-32" style="animation-delay: -5s; background: rgba(255, 255, 255, 0.02);"></div>
            <div class="bubble w-48 h-48 bottom-10 left-1/4" style="animation-delay: -2s;"></div>
            <div class="bubble w-80 h-80 top-1/2 -left-40" style="animation-delay: -10s; background: rgba(255, 255, 255, 0.03);"></div>
        </div>

                       <!-- Full-screen catch overlay: fades to solid after the panels split,
             so the swap to the dashboard happens underneath a solid color
             instead of a hard visual cut. -->
        <div x-data="{ visible: false }"
             x-on:auth-split.window="visible = true"
             class="fixed inset-0 bg-indigo-950 z-20 pointer-events-none transition-opacity duration-500 delay-700 ease-out"
             :class="visible ? 'opacity-100' : 'opacity-0'">
        </div>
                <!-- Centered Glass/Shadow Container — stays fully visible and doesn't
             fade itself; only its two child panels animate (slide apart), so
             the split is actually visible before the overlay fades everything
             out afterward. -->
                                <div class="relative w-full max-w-[1000px] h-auto min-h-[560px] sm:h-[600px] my-4 sm:my-0 rounded-3xl bg-white overflow-hidden flex flex-col md:flex-row z-10 shadow-[0_0_50px_rgba(0,0,0,0.3)] border border-white/10">
            
            <div class="container-glow bg-white/20"></div>

                       <!-- Left Side: Marketing Cover -->
            <div :class="splitting ? 'auth-anim-left' : ''"
                 class="hidden md:flex md:w-1/2 relative overflow-hidden flex-col justify-between p-10 group bg-indigo-600">
                <img src="{{ asset('images/mtc-logo-only.png') }}" alt="{{ \App\Services\ConfigurationService::getBusinessName() }}" class="absolute inset-0 w-full h-full object-cover" />
                <!-- Overlay for readability -->
                <div class="absolute inset-0 bg-black/45"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-orange-100/20 via-transparent to-transparent"></div>
                
                <div class="relative z-10 flex items-center gap-2">
                     <x-application-logo class="w-8 h-8 fill-current text-white" />
                     <span class="text-[16px] font-extrabold tracking-tight text-white uppercase">{{ \App\Services\ConfigurationService::getBusinessName() }}</span>
                </div>

                <div class="relative z-10 mt-auto">
                    <p class="text-slate-200 text-sm opacity-95 leading-relaxed max-w-sm">Manage your branch monitoring, sales data, and staff execution all in one centralized command center.</p>
                </div>
            </div>

                            <!-- Right Side: Form -->
            <div :class="splitting ? 'auth-anim-right' : ''"
                 class="w-full md:w-1/2 flex flex-col p-6 sm:p-10 lg:p-12 relative bg-white h-full overflow-y-auto no-scrollbar">
                <div class="flex-1 flex flex-col justify-center max-w-[380px] mx-auto w-full animate-form-enter">
                    <!-- Mobile only logo -->
                    <div class="flex items-center gap-2 mb-8 md:hidden justify-center hover:opacity-80 transition-opacity">
                         <x-application-logo class="w-8 h-8 fill-current text-indigo-600" />
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

