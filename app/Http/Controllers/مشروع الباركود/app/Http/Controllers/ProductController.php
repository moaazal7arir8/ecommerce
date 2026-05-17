<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\facades\DB;
use Illuminate\Support\facades\Storage;     

class ProductController extends Controller
{
    public function create(Request $request){
    $validateData = $request->validate([
        'name' => 'required|max:40|string',
        'describtion' => 'nullable|string|max:255',             
        'image' => 'nullable|image|mimes:png,jpg,jpeg,gif'
    ]);

    // التعامل مع الصورة فقط داخل try/catch
    if ($request->hasFile('image')) {
        try {
            $path = $request->file('image')->store('photo of products', 'public');
            $validateData['image'] = $path;
        } catch (\Exception $e) {
            // فقط إرجاع رد للمستخدم، دون تخزين أي خطأ
            return response()->json([
                'خطأ' => 'حدث خطأ أثناء رفع الصورة.'
            ], 500);
        }
    }
    $product = Product::create($validateData);
    return response()->json([
        'رسالة' => 'تم إنشاء المنتج بنجاح',
    ]);
}

    
    public function update(Request $request,$id){
        $validateData=$request->validate([
            'name'=>'max:40|string',
            'describtion'=>'string|max:255',
            'image'=>'image|mimes:png,jpg,jpeg,gif'
        ]);
        $product=Product::find($id);
        if(!$product){
             return response()->json([
        'رسالة'=>'هذا المنتج غير موجود'
        ]);
        }
         if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            // حفظ الصورة الجديدة
            $path=$request->file('image')->store('photo of products','public');
            $validateData['image']=$path;
        }
        $product->update($validateData);
       return response()->json([
        'رسالة'=>'تم التعديل',
        ]);
    }
    public function delete($id){
    $product=Product::find($id);
    if(!$product){
        return response()->json([
            'رسالة'=>'هذا المنتج غير موجود'
        ]);
    }
    if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
     $product->delete();
         return response()->json([
            'رسالة'=>'تم حذف المنتج'
        ]);
    }
}
