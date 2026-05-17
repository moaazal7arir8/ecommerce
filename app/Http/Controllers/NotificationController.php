<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DeviceToken;
use App\Models\Notification;

use App\Jobs\SendNotificationsToAllUsersJob;
use Illuminate\Support\facades\DB;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function showUserNotificationsNumber()
    {
        $result = DB::select(
            'SELECT numberOfNotifications FROM users WHERE id = ? LIMIT 1',
            [Auth::id()]
        );
        $number = $result ? $result[0]->numberOfNotifications : 0;
        return response()->json([
            'numberOfNotifications' => $number
        ]);
    }

    public function showUserNotifications()
    {
        $notifications = Notification::where(function ($query) {
            $query->where('user_id', Auth::id())
                ->orWhere('is_global', true);
        })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        User::where('id', Auth::id())->update([
            'numberOfNotifications' => 0
        ]);
        return response()->json($notifications);
    }

    public function sendNotificationToAll(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
        ]);
        $ids = User::where('role', 'user')->pluck('id')->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد مستخدمين على التطبيق بعد '
            ], 404);
        }
        $tokens = DeviceToken::join('users', 'device_tokens.user_id', '=', 'users.id')
            ->where('users.role', 'user')
            ->pluck('device_tokens.token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد مستخدمين مسجلين دخول على التطبيق بعد '
            ], 404);
        }
        SendNotificationsToAllUsersJob::dispatch(
            $request->title,
            $request->body,
            $ids
        );
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع المستخدمين'
        ]);
    }
    public function sendNotificationToAll2(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'b ody'  => 'required|string|max:1000',
        ]);
        $ids = User::where('role', 'user')->pluck('id')->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد مستخدمين على التطبيق بعد '
            ], 404);
        }
        $tokens = DeviceToken::join('users', 'device_tokens.user_id', '=', 'users.id')
            ->where('users.role', 'user')
            ->pluck('device_tokens.token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد مستخدمين مسجلين دخول على التطبيق بعد '
            ], 404);
        }
        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification(
                \Kreait\Firebase\Messaging\Notification::create(
                    $request->title,
                    $request->body
                )
            );
        \Kreait\Laravel\Firebase\Facades\Firebase::messaging()
            ->sendMulticast($message, $tokens);

        Notification::create([
            'title' => $request->title,
            'body' => $request->body,
            'is_global' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::whereIn('id', $request->ids)
            ->increment('numberOfNotifications');

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع المستخدمين'
        ]);
    }
}
