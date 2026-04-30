<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validateDate = $request->validate([
            'name' => 'required|string|max:45',
            'email' => 'required|string|email|max:45|unique:users,email',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        $validateDate['role'] = 'user';
        $user = User::create($validateDate);


        return response()->json([
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:45',
            'password' => 'required|string|min:8|max:255',
        ]);
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', $request->email)->first();

        $token = $user->createToken('mobile')->plainTextToken;

        if ($request->filled('fcm_token')) {
            DeviceToken::updateOrCreate(
                ['token' => $request->fcm_token],
                [
                    'user_id' => $user->id,
                    'platform' => $request->platform
                ]
            );
        }

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        //✅ احذف فقط توكن الجهاز الحالي (لا تحذف كل التوكنات)
        if ($request->filled('fcm_token')) {
            DeviceToken::where('user_id', $user->id)
                ->where('token', $request->fcm_token)
                ->delete();
        }
        // احذف access token الخاص بالمصادقة
        $user->currentAccessToken()->delete();
        return response()->json([
            'رسالة' => 'تم تسجيل الخروج بنجاح'
        ]);
    }
}
