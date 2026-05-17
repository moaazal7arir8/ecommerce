<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\facades\Auth;

class CheckIfNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && in_array($user->role, ['blocked_user', 'blocked_admin'])) {
            return response()->json([
                'message' => 'تم حظرك من التطبيق'
            ], 403);
        }
        // if ($user->role === 'user' || $user->role === 'admin') {
        //     if (System::where('app', $user->role)->exists()) {
        //         return response()->json([
         //             'message' => 'تم إيقاف التطبيق وسيتم إرسال إشعار عند عودته للعمل'
        //         ]);
        //     };
        // }

        return $next($request);
    }
}
