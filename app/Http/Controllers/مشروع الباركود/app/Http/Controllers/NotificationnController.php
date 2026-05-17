<?php

namespace App\Http\Controllers;

use App\Models\Notificationn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\facades\Auth;
use Illuminate\Support\facades\DB;

class NotificationnController extends Controller
{
    public function numberOfNotifications()
    {
        //    $number = DB::table('users')
        //         ->where('id', Auth::id())
        //         ->value('numberOfNotifications');
        //     return response()->json([
        //         'numberOfNotifications' => $number
        //     ]);
        $result = DB::select(
            'SELECT numberOfNotifications FROM users WHERE id = ? LIMIT 1',
            [Auth::id()]
        );
        $number = $result ? $result[0]->numberOfNotifications : 0;
        return response()->json([
            'numberOfNotifications' => $number
        ]);
    }

    public function showNotification()
    {
        $user = User::find(Auth::id());
        $user->update([
            'numberOfNotifications' => 0
        ]);
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json([
            'notifications' => $notifications
        ]);
    }
    // public function registers(){
    //     $registers=Notificationn::where('')
    // }
}
