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
        RateLimiter::for('forUser', function (Request $request) {
            return Limit::perMinutes(3, 10)
                ->by($request->ip() . '|' . $request->input('email'))
                ->response(function () {
                    return response()->json([
                        'message' => 'لقد تجاوزت عدد محاولات تسجيل الدخول، حاول بعد 3 دقائق'
                    ], 429);
                });
        });

        RateLimiter::for('forAdmin', function (Request $request) {
            return Limit::perMinutes(180, 10)
                ->by($request->ip() . '|' . $request->input('email'))
                ->response(function () {
                    return response()->json([
                        'message' => 'لقد تجاوزت عدد محاولات تسجيل الدخول، حاول بعد 3 ساعات'
                    ], 429);
                });
        });
    }
}
