<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\facades\Auth;
use App\Models\User;
use App\Models\Follow;

class FollowController extends Controller
{
    public function agents()
    {
        $user = User::find(Auth::id());
        $admins = User::where('role', 'admin')->where('country', $user->country)->get();
        return response()->json([
            'العملاء' => $admins
        ]);
    }

    public function createFollow($id)
    {
        $person = User::find($id);
        if (!$person) {
            return response()->json([
                'message' => 'هذا الوكيل لا يمكن متابعته لأنه قام بحذف حسابه'
            ]);
        }
        $exists = Follow::where('follower_id', Auth::id())
            ->where('followed_id', $person->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'أنت تتابع هذا الوكيل بالفعل']);
        }
        try {
            Follow::create([
                'follower_id' => Auth::id(),
                'followed_id' => $person->id
            ]);

            $person->increment('numberOfFollowers');
            return response()->json([
                'message' => 'لقد أصبحت تتابع هذا الوكيل'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ حاول مجدداً'
            ]);
        }
    }
    public function unFollow($id)
    {
        $person = User::find($id);
        $follow = Follow::where('follower_id', Auth::id())
            ->where('followed_id', $person->id)->first();
        if (!$follow) {
            return response()->json([
                'message' => 'تم الغاء متابعة هذا الوكيل بالفعل'
            ]);
        }
        try {
            $follow->delete();
            $person->decrement('numberOfFollowers');
            return response()->json([
                'message' => 'تم الغاء متابعة هذا الوكيل '
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ حاول مجدداً'
            ]);
        }
    }
    public function whoFollowers()
    { //for admin
        //$myFollowers=$user->followers;
        $myFollowers = DB::table('follows')
            ->join('users', 'follows.follower_id', '=', 'users.id')
            ->where('follows.followed_id', Auth::id())
            ->where('users.role', 'user')
            ->select(
                'users.id',
                'users.name',
                'users.phone_number',
                'users.country',
                'users.city',
                'users.points',
                'numberOfScan'
            )->orderBy('follows.created_at', 'desc') // ← الترتيب من الأحدث إلى الأقدم
            ->get();
        return response()->json([
            'العملاء الذين يتابعونني' => $myFollowers
        ]);
    }
    public function whoFollowersThisAgent($id)
    { //for super admin
        //$myFollowers=$user->followers;
        $Followers = DB::table('follows')
            ->join('users', 'follows.follower_id', '=', 'users.id')
            ->where('follows.followed_id', $id)
            ->where('users.role', 'user')
            ->select(
                'users.id',
                'users.name',
                'users.phone_number',
                'users.country',
                'users.city',
                'users.points',
                'users.points_total',
                'numberOfScan'
            )->orderBy('follows.created_at', 'desc') // ← الترتيب من الأحدث إلى الأقدم
            ->get();
        return response()->json([
            'العملاء الذين يتابعون هذا الوكيل' => $Followers
        ]);
    }
    public function followingsHim()
    { //for user
        //$iFollowingsHim=$user->followings;
        $iFollowingsHim = DB::table('follows')
            ->join('users', 'follows.followed_id', '=', 'users.id')
            ->where('follows.follower_id', Auth::id())
            ->where('users.role', 'admin')
            ->select(
                'users.id',
                'users.name',
                'users.phone_number'
            )->orderBy('follows.created_at', 'desc') // ← الترتيب من الأحدث إلى الأقدم
            ->get();
        return response()->json([
            'الوكلاء الذين تتابعهم' => $iFollowingsHim
        ]);
    }
    public function followingsHimmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm($id)
    {
        $heFollowingsHim = DB::table('follows')
            ->join('users', 'follows.followed_id', '=', 'users.id')
            ->where('follows.follower_id', $id)
            ->select(
                'users.id',
                'users.name',
                'users.phone_number'
            )->orderBy('follows.created_at', 'desc') // ← الترتيب من الأحدث إلى الأقدم
            ->get();
        return response()->json([
            'الوكلاء الذين يتابعهم هذا الزبون' => $heFollowingsHim
        ]);
    }
}
