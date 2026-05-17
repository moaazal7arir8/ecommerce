<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gift;
use App\Models\Product;
use Illuminate\Support\facades\DB;

class GiftController extends Controller
{
    public function createGiftsBox(Request $request)
    {
        // 1️⃣ Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer|min:1',
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string|max:255',
            'products.*.image' => 'nullable|image|mimes:png,jpg,jpeg,gif'
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // 2️⃣ إنشاء العرض
                $gift = Gift::create([
                    'name' => $validated['name'],
                    'points' => $validated['points'],
                ]);

                // 3️⃣ إنشاء المنتجات المرتبطة
                foreach ($validated['products'] as $product) {
                    $path = null;

                    if (isset($product['image'])) {
                        // رفع الصورة لكل منتج
                        $path = $product['image']->store('photo_of_products', 'public');
                    }

                    Product::create([
                        'gift_id' => $gift->id,
                        'name' => $product['name'],
                        'image' => $path,
                    ]);
                }
            });

            // 4️⃣ إذا وصلت هنا، كل شيء ناجح
            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء صندوق الهدايا بنجاح'
            ], 201);
        } catch (\Exception $e) {
            // 5️⃣ أي خطأ سيتم التراجع عنه بالكامل
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إنشاء صندوق الهدايا ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getGiftsBox_desc(Request $request)
    {
        $gift = Gift::orderBy('id', 'desc')
            ->with('products') // لجلب المنتجات المرتبطة
            ->paginate(7);

        return response()->json([
            'gift' => $gift
        ]);
    }
    public function getGiftsBox_descPoints(Request $request)
    {
        $gift = Gift::orderBy('points', 'desc') // أكثر نقاط أولًا
            ->with('products') // لجلب المنتجات المرتبطة
            ->paginate(7);

        return response()->json([
            'gift' => $gift
        ]);
    }
    public function getGiftsBox_ascPoints(Request $request)
    {
        $gift = Gift::orderBy('points', 'asc') // الاقل نقاط أولًا
            ->with('products') // لجلب المنتجات المرتبطة
            ->paginate(7);

        return response()->json([
            'gift' => $gift
        ]);
    }
    public function deleteGiftsBox($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $gift = Gift::findOrFail($id); // التأكد أن الصندوق موجود
                $gift->delete(); // سيحذف كل المنتجات المرتبطة تلقائيًا بفضل cascadeOnDelete
            });

            return response()->json([
                'message' => 'تم حذف صندوق الهدايا بنجاح .'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء حذف الصندوق.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
