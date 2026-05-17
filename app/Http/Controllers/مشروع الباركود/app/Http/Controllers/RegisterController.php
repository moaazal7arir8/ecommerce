<?php

namespace App\Http\Controllers;

use App\Models\Register;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
   public function allRegisters(){
    $registers = Register::orderBy('id', 'desc')
        ->paginate(10);

    return response()->json([
        'registers'=>$registers
    ]);
    }
   public function printRegisters(){
    $registers = Register::where('type','print')->orderBy('id', 'desc')
        ->paginate(10);

    return response()->json([
        'registers'=>$registers
    ]);
    }
   public function scanRegisters(){
    $registers = Register::where('type','scan')->orderBy('id', 'desc')
        ->paginate(10);

    return response()->json([
        'registers'=>$registers
    ]);
    }public function increaseRegisters(){
    $registers = Register::where('type','increase')->orderBy('id', 'desc')
        ->paginate(10);

    return response()->json([
        'registers'=>$registers
    ]);
    }public function reduceRegisters(){
    $registers = Register::where('type','reduce')->orderBy('id', 'desc')
        ->paginate(10);

    return response()->json([
        'registers'=>$registers
    ]);
    }
    public function deleteRegisters(Request $request){
    $x = $request->count;

    DB::transaction(function () use ($x) {

        $ids = DB::table('registers')
            ->orderBy('created_at', 'asc')
            ->limit($x)
            ->lockForUpdate() // مهم لمنع التداخل
            ->pluck('id');

        DB::table('registers')
            ->whereIn('id', $ids)
            ->delete();
    });

    return response()->json([
        'message' => "تم حذف أقدم {$x} سجلات بأمان"
    ]);
}
}