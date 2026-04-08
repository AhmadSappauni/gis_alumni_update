<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot()
    {
        Paginator::useBootstrap();

        // 🔥 TAMBAHKAN INI
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}