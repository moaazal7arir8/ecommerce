<?php

namespace App\Http\Controllers;

use App\Models\Exchange;
use Illuminate\Http\Request;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{


    public function createOffer(Request $request, $id)
    {
        $currency_exchange = Exchange::first();
        if (!$currency_exchange) {
            return response()->json([
                'message' => 'يجب تحديد سعر صرف الدولار اولا'
            ]);
        }
        // 1️⃣ Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer|min:1',
            'american_price' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string|max:255',
            'products.*.image' => 'nullable|image|mimes:png,jpg,jpeg,gif'
        ]);

        try {
            DB::transaction(function () use ($validated, $id) {
                // 2️⃣ إنشاء العرض
                $currency_exchange = Exchange::first()->currency_exchange;
                $validated['syrian_price'] = $validated['american_price'] * $currency_exchange;
                $offer = Offer::create([
                    'name' => $validated['name'],
                    'points' => $validated['points'],
                    'category_id' => $id,
                    'syrian_price' => $validated['syrian_price'],
                    'american_price' => $validated['american_price'],
                ]);

                // 3️⃣ إنشاء المنتجات المرتبطة
                foreach ($validated['products'] as $product) {
                    $path = null;

                    if (isset($product['image'])) {
                        // رفع الصورة لكل منتج
                        $path = $product['image']->store('photo_of_products', 'public');
                    }

                    Product::create([
                        'offer_id' => $offer->id,
                        'name' => $product['name'],
                        'image' => $path,
                    ]);
                }
            });

            // 4️⃣ إذا وصلت هنا، كل شيء ناجح
            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء العرض  بنجاح'
            ], 201);
        } catch (\Exception $e) {
            // 5️⃣ أي خطأ سيتم التراجع عنه بالكامل
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إنشاء العرض أو المنتجات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getOffersOfCategory_desc(Request $request, $categoryId)
    {
        $offers = Offer::where('category_id', $categoryId)
            ->orderBy('id', 'desc')
            ->with('products') // لجلب المنتجات المرتبطة
            ->paginate(7);

        return response()->json([
            'offers' => $offers
        ]);
    }
    public function getOffersOfCategory_descPoints(Request $request, $categoryId)
    {
        $offers = Offer::where('category_id', $categoryId)
            ->orderBy('points', 'desc') // أكثر نقاط أولًا
            ->with('products') // لجلب المنتجات المرتبطة
            ->paginate(7);

        return response()->json([
            'offers' => $offers
        ]);
    }
    public function getOffersOfCategory_ascSyrian_price(Request $request, $categoryId)
    {
        $offers = Offer::where('category_id', $categoryId)
            ->orderBy('syrian_price', 'asc') // الأقل سعرًا أولًا
            ->with('products') // لجلب المنتجات المرتبطة
            ->paginate(7);

        return response()->json([
            'offers' => $offers
        ]);
    }
    public function deleteOffer($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $offer = Offer::findOrFail($id); // التأكد أن العرض موجود
                $offer->delete(); // سيحذف كل المنتجات المرتبطة تلقائيًا بفضل cascadeOnDelete
            });

            return response()->json([
                'message' => 'تم حذف العرض .'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء حذف العرض.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
