<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\facades\DB;
use Illuminate\Support\facades\Storage;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    // public function createProduct(Request $request, $id)
    // {
    //     $validateData = $request->validate([
    //         'name' => 'required|max:40|string',
    //         'quantity' => 'required|min:1|integer',
    //         'price' => 'required|numeric',
    //         'profit' => 'required|numeric',
    //         'describtion' => 'nullable|string|max:255',
    //         'image' => 'nullable|image|mimes:png,jpg,jpeg,gif'
    //     ]);
    //     $category = Category::find($id);
    //     if (!$category) {
    //         return response()->json([
    //             'message' => 'هذا القسم لم يعد موجود'
    //         ]);
    //     }
    //     $validateData['category_id'] = $id;
    //     if ($request->hasFile('image')) {
    //         try {
    //             $path = $request->file('image')->store('photo of products', 'public');
    //             $validateData['image'] = $path;
    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 'خطأ' => 'حدث خطأ أثناء رفع الصورة.'
    //             ], 500);
    //         }
    //     }
    //     $product = Product::create($validateData);
    //     $page = 1;

    //     while (true) {
    //         $key = 'category_products_' . $id . '_page_' . $page;

    //         if (!Cache::has($key)) {
    //             break;
    //         }

    //         Cache::forget($key);
    //         $page++;
    //     }
    //     return response()->json([
    //         'رسالة' => 'تم إنشاء المنتج بنجاح',
    //     ]);
    // }


    // public function updateProduct(Request $request, $id)
    // {
    //     $validateData = $request->validate([
    //         'name' => 'max:40|string',
    //         'quantity' => 'min:1|integer',
    //         'price' => 'numeric',
    //         'profit' => 'numeric',
    //         'describtion' => 'string|max:255',
    //         'image' => 'image|mimes:png,jpg,jpeg,gif'
    //     ]);
    //     $product = Product::find($id);
    //     if (!$product) {
    //         return response()->json([
    //             'رسالة' => 'هذا المنتج غير موجود'
    //         ]);
    //     }
    //     if ($request->hasFile('image')) {
    //         if ($product->image && Storage::disk('public')->exists($product->image)) {
    //             Storage::disk('public')->delete($product->image);
    //         }
    //         // حفظ الصورة الجديدة
    //         $path = $request->file('image')->store('photo of products', 'public');
    //         $validateData['image'] = $path;
    //     }
    //     $product->update($validateData);

    //     $categoryId=$product->category->id;
    //     $page = 1;
    //     while (true) {
    //         $key = 'category_products_' . $categoryId . '_page_' . $page;

    //         if (!Cache::has($key)) {
    //             break;
    //         }

    //         Cache::forget($key);
    //         $page++;
    //     }
    //     return response()->json([
    //         'رسالة' => 'تم التعديل',
    //     ]);
    // }
    // public function deleteProduct($id)
    // {
    //     $product = Product::find($id);
    //     if (!$product) {
    //         return response()->json([
    //             'رسالة' => 'هذا المنتج غير موجود'
    //         ]);
    //     }
    //     if ($product->image && Storage::disk('public')->exists($product->image)) {
    //         Storage::disk('public')->delete($product->image);
    //     }
    //     $categoryId=$product->category->id;
    //     $product->delete();
     
    //     $page = 1;
    //     while (true) {
    //         $key = 'category_products_' . $categoryId . '_page_' . $page;

    //         if (!Cache::has($key)) {
    //             break;
    //         }

    //         Cache::forget($key);
    //         $page++;
    //     }
    //     return response()->json([
    //         'رسالة' => 'تم حذف المنتج'
    //     ]);
    // }
    private function clearTopProductsCache()
    {
        $page = 1;

        while (true) {
            $key = 'top_products_page_' . $page;

            if (!Cache::has($key)) {
                break;
            }

            Cache::forget($key);
            $page++;
        }
    }
    public function createProduct(Request $request, $id)
    {
        $validateData = $request->validate([
            'name' => 'required|max:40|string',
            'quantity' => 'required|min:1|integer',
            'price' => 'required|numeric',
            'profit' => 'required|numeric',
            'describtion' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif'
        ]);

        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'هذا القسم لم يعد موجود'
            ]);
        }

        $validateData['category_id'] = $id;

        if ($request->hasFile('image')) {
            try {
                $path = $request->file('image')->store('photo of products', 'public');
                $validateData['image'] = $path;
            } catch (\Exception $e) {
                return response()->json([
                    'خطأ' => 'حدث خطأ أثناء رفع الصورة.'
                ], 500);
            }
        }

        Product::create($validateData);

        $this->clearTopProductsCache();

        return response()->json([
            'رسالة' => 'تم إنشاء المنتج بنجاح'
        ]);
    }
    public function updateProduct(Request $request, $id)
    {
        $validateData = $request->validate([
            'name' => 'max:40|string',
            'quantity' => 'min:1|integer',
            'price' => 'numeric',
            'profit' => 'numeric',
            'describtion' => 'string|max:255',
            'image' => 'image|mimes:png,jpg,jpeg,gif'
        ]);

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'رسالة' => 'هذا المنتج غير موجود'
            ]);
        }

        if ($request->hasFile('image')) {

            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $path = $request->file('image')->store('photo of products', 'public');
            $validateData['image'] = $path;
        }

        $product->update($validateData);

        $this->clearTopProductsCache();

        return response()->json([
            'رسالة' => 'تم التعديل'
        ]);
    }
    public function deleteProduct($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'رسالة' => 'هذا المنتج غير موجود'
            ]);
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        $this->clearTopProductsCache();

        return response()->json([
            'رسالة' => 'تم حذف المنتج'
        ]);
    }

}
