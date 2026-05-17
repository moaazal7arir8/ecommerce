<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('againstBruteForce', function (Request $request) {
            return Limit::perMinutes(3, 10)
                ->by($request->ip() . '|' . $request->input('email'))
                ->response(function () {
                    return response()->json([
                        'message' => 'لقد تجاوزت عدد محاولات تسجيل الدخول، حاول بعد 3 دقائق'
                    ], 429);
                });
        });
    }
}
