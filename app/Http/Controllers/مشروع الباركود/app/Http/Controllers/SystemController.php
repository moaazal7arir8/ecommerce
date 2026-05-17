<?php

namespace App\Http\Controllers;

use App\Models\System;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function setAdminVersion(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'version' => 'required|integer',
            'link' => 'required|string'
        ]);
        $validated['app'] = 'admin';
        $system = System::where('app', 'admin')->first();
        if (!$system) {
            System::create($validated);
            return response()->json([
                'message' => 'تمت العملية بنجاح'
            ]);
        }
        $system->update($validated);
        return response()->json([
            'message' => 'تمت العملية بنجاح'
        ]);
    }
    public function setUserVersion(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'version' => 'required|integer',
            'link' => 'required|string'
        ]);
        $validated['app'] = 'user';
        $system = System::where('app', 'user')->first();
        if (!$system) {
            System::create($validated);
            return response()->json([
                'message' => 'تمت العملية بنجاح'
            ]);
        }
        $system->update($validated);
        return response()->json([
            'message' => 'تمت العملية بنجاح'
        ]);
    } 
    public function getAdminVersion()
    { 
        $system = System::where('app', 'admin')->first();
        if (!$system) {
            return response()->json([
                'message' => 'إصدر الوكيل غير معين'
            ]);
        }
        return response()->json([
            'system' => $system
        ]);
    }
    public function getUserVersion()
    {
        $system = System::where('app', 'user')->first();
        if (!$system) {
            return response()->json([
                'message' => 'إصدر الزبون غير معين'
            ]);
        }
        return response()->json([
            'system' => $system
        ]);
    }
    public function checkAdminVersion() {
    
    }
    public function checkUserVersion() {

    }
}
