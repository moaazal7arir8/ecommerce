<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function register(Request $request)
    {
        $validateDate = $request->validate([
            'name' => 'required|string|max:45',
            'email' => 'required|string|email|max:45|unique:users,email',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        $validateDate['role'] = 'admin';
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

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        // احذف access token الخاص بالمصادقة
        $user->currentAccessToken()->delete();
        return response()->json([
            'رسالة' => 'تم تسجيل الخروج بنجاح'
        ]);
    }
}
