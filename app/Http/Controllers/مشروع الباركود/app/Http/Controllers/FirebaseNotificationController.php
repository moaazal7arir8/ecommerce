<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\facades\DB;
use App\Models\User;
use App\Models\Category;
use App\Models\Notificationn;
use App\Models\Device_tokens;
use App\Jobs\SendNotificationToFollowers;
use App\Jobs\SendNotificationsBySuperAdmin;
use App\Models\Follow;
use Illuminate\Support\facades\Auth;

class FirebaseNotificationController extends Controller
{
    /**
     * 1️⃣ إرسال إشعار لمستخدم واحد (عن طريق user_id)
     */

    public function sendToUser(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
        ]);

        $user = User::findOrFail($id);
        if ($request->title == "حظر هذا") {
            if ($user->role == 'admin') {
                $user->update([
                    'role' => 'blocked_admin'
                ]);
            } else {
                $user->update([
                    'role' => 'blocked_user'
                ]);
            }
            return response()->json([
                'message' => 'تم حظر هذا المستخدم'
            ]);
        }
        $tokens = Device_Tokens::where('user_id', $id)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إرسال الإشعار له لأنه أنها تسجيله في التطبيق من خلال تسجيل الخروج أو قام بمسح التطبيق'
            ]);
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create(
                $validated['title'],
                $validated['body']
            ));

        try {
            Firebase::messaging()->sendMulticast($message, $tokens);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال الإشعار'
            ]);
        }

        $user->increment('numberOfNotifications');

        Notificationn::create([
            'title' => $validated['title'],
            'body'  => $validated['body'],
            'user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار بنجاح'
        ]);
    }
    /**
     * 2️⃣ إرسال إشعار لمستخدمين حسب البلد
     */
    public function sendToCountry(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'body'    => 'required|string|max:1000',
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'
        ]);


        $ids = User::where('country', $request->country)->pluck('id');

        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد توكينس في هذه المحافظة'
            ], 404);
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create(
                $request->title,
                $request->body
            ));

        Firebase::messaging()->sendMulticast($message, $tokens);
        $users = User::where('country', $request->country)->get();
        foreach ($users as $userr) {
            $userr->increment('numberOfNotifications', 1);
            Notificationn::create([
                'title' => $request->title,
                'body' => $request->body,
                'user_id' => $userr->id
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => "تم إرسال الإشعار لمستخدمي {$request->country}"
        ]);
    }
    public function sendTofollow(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
        ]);

        $admin = $request->user(); // أسرع وأوضح من Auth::user()

        // استخرج IDs المتابعين (مع استثناء المحظورين)
        $followerIds = DB::table('follows')
            ->join('users', 'follows.follower_id', '=', 'users.id')
            ->where('follows.followed_id', Auth::id())
            ->where('users.role', 'user')
            ->pluck('follows.follower_id')
            ->toArray();

        if (empty($followerIds)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد متابعين'
            ]);
        }
        // جلب التوكنات دفعة واحدة + إزالة التكرار والقيم الفارغة
        $tokens = Device_tokens::whereIn('user_id', $followerIds)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن ارسال الاشعار لمتابعينك لانهم أنهوا تسجيلهم في التطبيق من خلال تسجيل الخروج أو قاموا بمسح التطبيق'
            ]);
        }
        SendNotificationToFollowers::dispatch(
            $validated['title'],
            $validated['body'],
            $admin->id,
            $admin->name,
            $followerIds // نمررهم جاهزين
        );
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع المتابعين بنجاح'
        ]);
    }

    /**
     * 3️⃣ إرسال إشعار لجميع المستخدمين
     */

    public function sendToAll(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
        ]);
        $ids = User::whereNotIn('role', ['blocked_admin', 'blocked_user', 'super_admin'])->pluck('id')->toArray();

        $tokens = Device_Tokens::join('users', 'device_tokens.user_id', '=', 'users.id')
            ->whereNotIn('users.role', ['blocked_admin', 'blocked_user','super_admin'])
            ->pluck('device_tokens.token')
            ->toArray();


        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد مستخدمين على التطبيق بعد '
            ], 404);
        }
        // ✅ إرسال للـ Queue
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع المستخدمين'
        ]);
    }
    public function sendToAllUsers(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
        ]);

        $ids = User::where('role', 'user')->pluck('id')->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن بعد هنا   '
            ], 404);
        }
        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن بعد هنا'
            ], 404);
        }
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع الزبائن'
        ]);
    }
    public function sendToAllAdmins(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
        ]);

        $ids = User::where('role', 'admin')->pluck('id')->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد وكلاء بعد هنا   '
            ], 404);
        }
        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد وكلاء بعد هنا'
            ], 404);
        }
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع الوكلاء'
        ]);
    }
    public function sendToUsersAccordingToCountry(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'

        ]);
        $ids = User::where('role', 'user')->where('country', $request->country)->pluck('id')->toArray();

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن بعد هنا   '
            ], 404);
        }

        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن بعد هنا'
            ], 404);
        }
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع زبائن هذه المحافظة'
        ]);
    }
    public function sendToAdminsAccordingToCountry(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'

        ]);
        $ids = User::where('role', 'admin')->where('country', $request->country)->pluck('id')->toArray();

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد وكلاء بعد هنا   '
            ], 404);
        }

        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد وكلاء بعد هنا'
            ], 404);
        }
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع وكلاء هذه المحافظة'
        ]);
    }
    public function sendToUsersAccordingCategory(Request $request, $id)
    {
        $request->validate([
            'title'   => 'required|string',
            'body'    => 'required|string',
        ]);
        $category = Category::find($id);
         if (!$category) {
        return response()->json([
            'success' => false,
            'message' => 'الفئة غير موجودة'
        ], 404);
    }
        // $ids = $category->users()
        //     ->where('role', 'user')
        //     ->pluck('id')->toArray();
        $ids = DB::table('users')
            ->join('category_user', 'users.id', '=', 'category_user.user_id')
            ->where('category_user.category_id', $category->id)
            ->where('users.role', 'user')
            ->pluck('users.id')
            ->toArray();

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن في هذه الفئة'
            ], 404);
        }

        // جلب التوكنات
        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن في هذه الفئة'
            ], 404);
        }
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لزبائن هذه الفئة'
        ]);
    }
    public function sendToAdminsAccordingCategory(Request $request, $id)
    {
        $request->validate([
            'title'   => 'required|string',
            'body'    => 'required|string',
        ]);
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'الفئة غير موجودة'
            ], 404);
        }
        // $ids = $category->users()
        //     ->where('role', 'admin')
        //     ->pluck('id')->toArray();
        $ids = DB::table('users')
            ->join('category_user', 'users.id', '=', 'category_user.user_id')
            ->where('category_user.category_id', $category->id)
            ->where('users.role', 'admin')
            ->pluck('users.id')
            ->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد وكلاء في هذه الفئة'
            ], 404);
        }

        // جلب التوكنات
        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد وكلاء في هذه الفئة'
            ], 404);
        }
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لوكلاء هذه الفئة'
        ]);
    }
    public function sendToUsersAccordingToCountryAndCategory(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'

        ]);

        $category = Category::find($id);
        $ids = $category->users()->where('country', $request->country)->where('role', 'user')->pluck('users.id')->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن بعد هنا   '
            ], 404);
        }
        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن بعد هنا'
            ], 404);
        }
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع الزبائن في هذه المحافظة في هذه الفئة'
        ]);
    }

    public function sendToAdminsAccordingToCountryAndCategory(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'

        ]);

        $category = Category::find($id);
        $ids = $category->users()->where('country', $request->country)->where('role', 'admin')->pluck('users.id')->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد وكلاء بعد هنا   '
            ], 404);
        }
        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد وكلاء بعد هنا'
            ], 404);
        }

        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار لجميع الوكلاء في هذه المحافظة في هذه الفئة'
        ]);
    }

    public function snd_Notification_To_The_Customers_Of_This_Agent(Request $request, $id)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'body'    => 'required|string|max:1000',
        ]);

        // استخرج IDs المتابعين

        $ids = DB::table('follows')
            ->join('users', 'follows.follower_id', '=', 'users.id')
            ->where('follows.followed_id', $id)
            ->where('users.role', 'user')
            ->pluck('follows.follower_id')->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن عنده  '
            ], 404);
        }
        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد زبائن عنده  '
            ], 404);
        }
        SendNotificationsBySuperAdmin::dispatch(
            $request->title,
            $request->body,
            $ids
        );
        return response()->json([
            'success' => true,
            'message' => "تم إرسال الإشعار لجميع زبائنه "
        ]);
    }
}

