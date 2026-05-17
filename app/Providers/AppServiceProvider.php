<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Livewire\Livewire;
use App\Http\Controllers\Auth\ForgotPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Let Vite emit the real stylesheet tag without the extra CSS preload,
        // which can trigger false-positive "preloaded but not used" warnings.
        Vite::usePreloadTagAttributes(
            fn ($src, $url, $chunk, $manifest) => str_ends_with($url, '.css') ? false : []
        );

        // Dynamically override the base URL to support subdirectory deployments (like local XAMPP)
        // without hardcoding paths that would break root-domain production deployments (like Hostinger).
        if (!app()->runningInConsole()) {
            app()->booted(function () {
                if (app()->bound('request')) {
                    $request = app('request');
                    \Illuminate\Support\Facades\URL::forceRootUrl($request->getSchemeAndHttpHost() . $request->getBasePath());
                }
            });
        }

        Livewire::component('auth.forgot-password', ForgotPassword::class);
    }
}
