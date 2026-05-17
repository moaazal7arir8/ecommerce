<?php

namespace App\Http\Middleware;

use App\Models\System;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOfAdminVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->route('id');
        $system = System::where('app', 'admin')->first();
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
