<?php

namespace App\Http\Controllers;

use App\Models\Exchange;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExchangeController extends Controller
{
    public function set_Dollar_Exchange_Rate(Request $request)
    {
        $validated = $request->validate([
            'currency_exchange' => 'required|numeric|min:0'
        ]);

        try {
            DB::transaction(function () use ($validated) {

                // 1️⃣ تحديث أو إنشاء سعر الصرف
                $exchange = Exchange::updateOrCreate(
                    ['id' => 1],
                    ['currency_exchange' => $validated['currency_exchange']]
                );

                // 2️⃣ تحديث جميع العروض دفعة واحدة 🔥
                DB::table('offers')->update([
                    'syrian_price' => DB::raw('american_price * ' . $exchange->currency_exchange)
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث سعر الصرف وتعديل جميع العروض بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء التحديث',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function get_Dollar_Exchange_Rate()
    {
        $exchange = Exchange::first();
        if (!$exchange) {
            return response()->json([
                'سعر تصريف الدولار' => 0
            ]);
        }
        return response()->json([
            'سعر تصريف الدولار' => $exchange
        ]);
    }
}
