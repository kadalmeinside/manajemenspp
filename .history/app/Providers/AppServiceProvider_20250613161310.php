<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event; // 1. Impor fasad Event
use Illuminate\Auth\Events\Login;      // 2. Impor event Login
use App\Listeners\LogSuccessfulLogin; 

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(
            Login::class,
            LogSuccessfulLogin::class
        );
    }
}
