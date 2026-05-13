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

        Livewire::component('auth.forgot-password', ForgotPassword::class);
    }
}
