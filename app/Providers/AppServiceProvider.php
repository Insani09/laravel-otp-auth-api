<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // GeoNames berkuota terbatas: batasi 30 request/menit per IP.
        // Data dibungkus cache server-side, jadi beban aktual ke GeoNames
        // jauh lebih kecil dari batas ini.
        RateLimiter::for('geonames', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
