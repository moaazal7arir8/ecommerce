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
        $category->delete();
        return response()->json([
            'message' => 'تم حذف الصنف بنجاح'
        ]);
    }

    public function categories()
    {
        $categories = Category::all();
        return response()->json([
            'categories' => $categories
        ]);
    }
    public function updateCategoriesBySuperAdmin(Request $request, $id)
    {
        $validateData = $request->validate([
            // 👇 Validation للأصناف
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);
        // CategoryUser::where('user_id', Auth::id())->delete();
        $user = User::find($id);
        $user->categories()->detach();


        foreach ($validateData['categories'] as $categoryId) {
            DB::table('category_user')->insert([
                'user_id' => $user->id,
                'category_id' => $categoryId
            ]);
        }

        return response()->json([
            'رسالة' => 'تم اعادة تعيين هذا الوكيل',
        ],);
    }

    public function showAdminsOfCategory($id)
    {
        $category = Category::find($id);
        $admins = $category->users()->where('role', 'admin')->get();
        return response()->json([
            'admins' => $admins
        ]);
    }
    public function showUsersOfCategory($id)
    {
        $category = Category::find($id);
        $users = $category->users()
            ->where('role', 'user')
            ->orderBy('category_user.categoricalUserPoints', 'desc')
            ->get();
        return response()->json([
            'users' => $users
        ]);
    }
    public function sendByCategory(Request $request, $id)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'body'    => 'required|string|max:1000',
        ]);
        $category = Category::find($id);
        $users = $category->users;
        $ids = $users->pluck('id')->toArray();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد مستخدمين في هذه الفئة'
            ], 404);
        }
        $tokens = Device_tokens::whereIn('user_id', $ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد توكينس في هذه الفئة'
            ], 404);
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create(
                $request->title,
                $request->body
            ));

        Firebase::messaging()->sendMulticast($message, $tokens);
        foreach ($users as $userr) {
            $userr->increment('numberOfNotifications', 1);
            Notificationn::create([
                'title' => $request->title,
                'body' => $request->body,
                'user_id' => $userr->id
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => "تم إرسال الإشعار  لمستخدمي هذذه الفئة"
        ]);
    }
    public function showCategoriesForAdmin($id)
    {
        $user = User::find($id);
        $categories = $user->categories;
        return response()->json([
            'فئاته' => $categories
        ]);
    }
    public function updateCategories(Request $request, $id)
    {
        $validateData = $request->validate([
            // 👇 Validation للأصناف
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);
        // CategoryUser::where('user_id', Auth::id())->delete();
        $user = User::find($id);
        $user->categories()->detach();


        foreach ($validateData['categories'] as $categoryId) {
            DB::table('category_user')->insert([
                'user_id' => Auth::id(),
                'category_id' => $categoryId
            ]);
        }

        return response()->json([
            'رسالة' => 'تم اعادة التعيين ',
        ],);
    }
}
