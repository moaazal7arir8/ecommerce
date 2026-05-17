<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\facades\Auth;

class CheckIfSuperAdminOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
     $user = Auth::user();
        if($user->role =='super_admin' || $user->role =='admin'){
        return $next($request);
        }
        else{
            return response()->json([
            'message' => 'هذه الصلاحية غير متوفرة لك'
        ]);
        }  
    }
}
