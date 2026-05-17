<?php

namespace App\Http\Controllers;

use Illuminate\Support\facades\Auth;
use App\Models\Barcode;
use App\Models\Category;
use App\Models\CategoryUser;
use App\Models\Follow;
use App\Models\User;
use App\Models\Notificationn;
use App\Models\Register;
use Illuminate\Http\Request;
use App\Jobs\StoreAndPrintBarcodesJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use App\Jobs\SendNotificationToFollowers;


class BarcodeController extends Controller
{
    public function  storeAndPrint(Request $request, $id)
    {
        $request->validate([
            'count'  => 'required|integer|min:1|max:32',
            'points' => 'required|integer|min:1'
        ]);
        DB::beginTransaction();

        try {
            $admin = User::lockForUpdate()->findOrFail(Auth::id());
            if ($admin->numberOfPointsAllowed <= $admin->pointsConsumed) {
                DB::rollBack();
                return response()->json([
                    'message' => 'لقد وصلت الى الحد الاقصى من عدد النقاط المسموح لك بها',
                ]);
            }
            if ($admin->numberOfPointsAllowed < $admin->pointsConsumed + $request->points * $request->count) {
                DB::rollBack();
                return response()->json([
                    'message' => 'لا يمكن القيام بالعملية لأنك ستتجاوز حد النقاط المسموح لك بها',
                ]);
            }
            $admin->update([
                'numberOfPrint' => $admin->numberOfPrint + $request->count,
                'pointsConsumed' => $admin->pointsConsumed + $request->points * $request->count
            ]);

            $barcodes = collect();

            for ($i = 0; $i < $request->count; $i++) {

                $barcode = Barcode::create([
                    'code'   => Str::uuid()->toString(),
                    'points' => $request->points,
                    'user_id' => $admin->id,
                    'category_id' => $id
                ]);
                $barcode->load('category'); // 👈 مهم
                // ✅ توليد QR بدون imagick
                $result = Builder::create()
                    ->writer(new PngWriter())
                    ->data($barcode->code)
                    ->size(200)
                    ->margin(10)
                    ->build();

                $barcode->qr_image = base64_encode($result->getString());

                $barcodes->push($barcode);
            }
            Register::create([
                'register' => " قام الوكيل {$admin->name} بطباعة {$request->count} باركودات بحاصل نقاط " . ($request->points * $request->count),
                'type' => 'print'
            ]);
            DB::commit();

            $pdf = Pdf::loadView('admin.barcodes.print', compact('barcodes'));
            return $pdf->download('barcodes.pdf');
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ حاول مجددا',
            ]);
        }
    }
    public function scan(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            DB::beginTransaction();
            $user = User::where('id', Auth::id())->lockForUpdate()->first();
            $barcode = Barcode::where('code', $request->code)
                ->lockForUpdate()
                ->first();

            if (!$barcode) {
                DB::rollBack();
                return response()->json([
                    'message' => 'الباركود غير صحيح',
                    'status' => 'error'
                ]);
            }

            if ($barcode->is_used) {
                DB::rollBack();
                return response()->json([
                    'message' => 'تم استخدام هذا الباركود مسبقاً',
                    'status' => 'error'
                ]);
            }

            $agent = $barcode->user;

            $follow = Follow::where('follower_id', Auth::id())
                ->where('followed_id', $agent->id)
                ->first();

            if (!$follow) {
                Follow::create([
                    'follower_id' => Auth::id(),
                    'followed_id' => $agent->id
                ]);

                $agent->increment('numberOfFollowers', 1);
            }

            $barcode->update([
                'is_used' => true,
                'used_by' => $user->name,
            ]);

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'points' => DB::raw('points + ' . (int)$barcode->points),
                    'points_total' => DB::raw('points_total + ' . (int)$barcode->points),
                ]);

            $user->increment('numberOfScan', 1);

            $category = $barcode->category;

            if ($category) {
                $relation = DB::table('category_user')
                    ->where('user_id', Auth::id())
                    ->where('category_id', $category->id)
                    ->first();

                if (!$relation) {
                    DB::table('category_user')->insert([
                        'user_id' => Auth::id(),
                        'category_id' => $category->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('category_user')
                    ->where('user_id', Auth::id())
                    ->where('category_id', $category->id)
                    ->increment('categoricalUserPoints', $barcode->points);
            }

            Register::create([
                "register" => "قام {$user->name} بمسح باركود {$barcode->points} نقطة لصالح {$agent->name}",
                "type" => "scan"
            ]);

            DB::commit();

            return response()->json([
                'success'        => true,
                'earned_points' => $barcode->points,
                'total_points'  => $user->points,
                'message'        => 'تمت إضافة النقاط بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ حاول مجددا',
                'status' => 'error'
            ]);
        }
    }
    public function code_verification(Request $request, $id)
    {
        $user = User::find($id);
        if ($user->code != $request->code) {
            return response()->json([
                'message' => 'الكود غر صحيح'
            ]);
        }
        $code = mt_rand(000000000, 999999999);
        $user->update([
            'code' => $code
        ]);
        return response()->json([
            'message' => 'The user code is correct'
        ]);
    }

    public function updatePoints($id, Request $request)
    {
        $user = User::findOrFail($id);
        if ($request->points < 0 || $user->points < $request->points) {
            return response()->json([
                'message' => " ❌عملية غير صحيحة ❌",
                'status' => 'error'
            ]);
        }
        $request->validate([
            'points' => 'required|integer'
        ]);

        $admin = User::find(Auth::id());

        DB::beginTransaction();
        try {

            $nativePoints = $user->points;

            // تحديث النقاط
            $user->update([
                'points' => $request->points,
            ]);

            Register::create([
                'register' => "قام الوكيل {$admin->name} بتنقيص نقاط {$user->name} من {$nativePoints} إلى {$user->points} ",
                'type' => 'reduce'
            ]);

            // إنشاء إشعار
            Notificationn::create([
                'title' => "{$user->name}",
                'body' => "قام الوكيل {$admin->name} بتنقيص نقاطك من {$nativePoints} إلى {$user->points}",
                'user_id' => $user->id
            ]);
            // زيادة عدد الإشعارات
            $user->increment('numberOfNotifications', 1);

            DB::commit();

            return response()->json([
                'message' => 'تمت العملية بنجاح',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ حاول مجددا',
                'status' => 'error'
            ]);
        }
    }
    public function updatePointsBySuperAdmin($id, Request $request)
    {
        $request->validate([
            'points' => 'required|integer'
        ]);
        $user = User::find($id);

        if ($user->points >= $request->points) {
            $user->update([
                'points' => $request->points,
            ]);
            return response()->json([
                'message' => 'تمت العملية بنجاح'
            ]);
        }
        $adding = $request->points - $user->points;
        $user->update([
            'points' => $request->points,
            'points_total' => $user->points_total + $adding
        ]);
        return response()->json([
            'message' => 'تمت العملية بنجاح'
        ]);
    }

    public function editNumberOfPointsAllowed($id, Request $request)
    {

        $request->validate([
            'numberOfPointsAllowed' => 'required|integer'
        ]);
        $user = User::find($id);
        $user->update([
            'numberOfPointsAllowed' => $request->numberOfPointsAllowed
        ]);
        return response()->json([
            'message' => 'تمت العملية بنجاح'
        ]);
    }
}
