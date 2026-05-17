<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MonitorPerformance
{
    public function handle(Request $request, Closure $next)
    {
        // البداية
        $startMemory = memory_get_usage();
        $startTime = microtime(true);

        $response = $next($request);

        // النهاية
        $endMemory = memory_get_usage();
        $endTime = microtime(true);

        $memoryUsed = $endMemory - $startMemory;
        $timeTaken = $endTime - $startTime;

        \Log::info('API Performance', [
            'url' => $request->fullUrl(),
            'memory_kb' => round($memoryUsed / 1024, 2),
            'time_ms' => round($timeTaken * 1000, 2),
            'peak_memory_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
        ]);

        return $response;
    }
}