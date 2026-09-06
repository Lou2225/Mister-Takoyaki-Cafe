<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Services\ConfigurationService::getBusinessName() }} - Customer Feedback</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 selection:bg-rose-500 selection:text-white">
        <div class="min-h-screen flex flex-col sm:justify-center items-center p-4">
            
            <div class="w-full max-w-sm w-full mx-auto relative z-10 transition-all duration-300">
                <!-- Branding Header -->
                <div class="text-center mb-8 flex flex-col items-center justify-center">
                    @php
                        $logoUrl = \App\Services\ConfigurationService::getBusinessLogoUrl();
                    @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Business Logo" class="w-16 h-16 rounded-2xl shadow-lg object-cover mb-4">
                    @else
                        <div class="w-16 h-16 bg-gradient-to-tr from-rose-500 to-rose-400 rounded-2xl shadow-lg flex items-center justify-center text-white font-black text-2xl tracking-tighter mb-4 shadow-rose-500/30">
                            MTC
                        </div>
                    @endif
                    <h1 class="text-xl font-black tracking-tight text-gray-900 mb-1">We value your feedback</h1>
                    <p class="text-[13px] text-gray-500 font-medium">Help us improve your {{ \App\Services\ConfigurationService::getBusinessName() }} experience</p>
                </div>

                {{ $slot }}

            </div>
            
            <div class="mt-8 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest relative z-10">
                &copy; {{ date('Y') }} {{ \App\Services\ConfigurationService::getBusinessName() }}
            </div>
            
            <!-- Optional Background decorative element -->
            <div class="fixed top-0 inset-x-0 h-64 bg-gradient-to-b from-rose-50 to-gray-50 z-0 pointer-events-none"></div>
        </div>

        @livewireScripts
    </body>
</html>


