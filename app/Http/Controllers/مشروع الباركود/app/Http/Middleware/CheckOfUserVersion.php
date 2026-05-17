<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\System;

class CheckOfUserVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->route('id');
        $system = System::where('app', 'user')->first();
        if (!$system) {
            return $next($request);
        }
        if ($id >= $system->version) {
            return $next($request);
        }
        return response()->json([
            'system' => $system
        ]);
    }
}
