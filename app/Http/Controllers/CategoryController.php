<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryUser;
use Illuminate\Support\facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\facades\Auth;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\User;
use App\Models\Notificationn;
use App\Models\Device_tokens;
use App\Models\Product;

class CategoryController extends Controller
{
    public function createCategory(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|string|unique:categories,name'
        ]);
        Category::create($validateData);
        return response()->json([
            'message' => 'تم انشاء الصنف بنجاح'
        ]);
    }
    public function updateCategory(Request $request, $id)
    {
        $validateData = $request->validate([
            'name' => 'required|string|unique:categories,name'
        ]);
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'هذا الصنف لم يعد متاح التعديل عليه لان تم حذفه'
            ]);
        }

        $category->update($validateData);

        return response()->json([
            'message' => 'تم تحديث اسم الصنف بنجاح'
        ]);
    }
    public function deleteCategory($id)
    {
        $category = Category::find($id);
        if(!$category){
           return response()->json([
            'message' => 'تم حذف الصنف بالفعل'
        ]);  
        }
        $category->delete();
        return response()->json([
            'message' => 'تم حذف الصنف بنجاح'
        ]);
    }

    public function showCategories()
    {
        $categories = Category::all();
        return response()->json([
            'categories' => $categories
        ]);
    }

    public function showCategoryProducts(Request $request, $categoryId)
    {
        $products = Product::where('category_id', $categoryId)
            ->orderBy('created_at', 'desc') 
            ->paginate(10);

        return response()->json([
            '$products' => $products
        ]);
    }
}
