<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Livewire\Livewire;
use App\Http\Controllers\Auth\ForgotPassword;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

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
                // previous_login_at = the sign-in before this one (what the profile card shows)
        Event::listen(Login::class, function (Login $event) {
            try {
                DB::table('users')
                    ->where('id', $event->user->getAuthIdentifier())
                    ->update([
                        'previous_login_at' => DB::raw('last_login_at'),
                        'last_login_at'     => now(),
                    ]);
            } catch (\Throwable $e) {
                report($e); // never block a login because of this
            }
        });
        
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
