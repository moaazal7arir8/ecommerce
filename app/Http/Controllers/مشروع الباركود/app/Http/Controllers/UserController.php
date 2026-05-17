<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Device_tokens;
use App\Models\Category;
use App\Models\Barcode;
use Illuminate\Support\facades\DB;
use Illuminate\Support\facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\facades\Mail;
use App\Mail\Forgetpassword;
use App\Models\CategoryUser;
use App\Models\Notificationn;
use App\Models\Register;
use Faker\Core\Barcode as CoreBarcode;
use RegexIterator;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validateDate = $request->validate([
            'name' => 'required|string|max:45',
            'email' => 'required|string|email|max:45|unique:users,email',
            'phone_number' => 'required|string|max:15',
            'password' => 'required|string|min:8|max:255|confirmed',
            'country' => 'required',
            'city' => 'required',
        ]);
        $code = mt_rand(000000000, 999999999);
        $validateDate['code'] = $code;
        $user = User::create($validateDate);

        return response()->json([
            'رسالة' => 'تم انشاء حساب لك لدينا والخطوة التالية عليك أن تقوم بتسجيل الدخول عبر ايميل وكلمة سر هذا الحساب',
            'حسابك' => $user
        ]);
    }
    function login(Request $request)
    {
        // ✳️ التحقق من البيانات
        $request->validate([
            'email' => 'required|string|email|max:45',
            'password' => 'required|string|max:255',
        ]);
        // ✳️ محاولة تسجيل الدخول
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'رسالة' => 'يوجد خطأ في البيانات'
            ], 401);
        }
        // ✳️ جلب المستخدم بعد النجاح
        $user = User::where('email', $request->email)->first();

        // ✳️ منع الأدمن من الدخول (حسب كودك)
        if ($user->role == 'admin') {
            return response()->json([
                'message' => 'لا يمكنك الوصول إلى هذا التطبيق'
            ], 403);
        }

        // ✳️ إنشاء التوكن
        $token = $user->createToken('mobile')->plainTextToken;

        // ✳️ حفظ device token (FCM)
        if ($request->filled('fcm_token')) {
            Device_tokens::updateOrCreate(
                ['token' => $request->fcm_token],
                [
                    'user_id' => $user->id,
                    'platform' => $request->platform
                ]
            );
        }

        // ✳️ الرد النهائي
        return response()->json([
            'رسالة' => 'تم تسجيل الدخول بنجاح',
            'user' => $user,
            'token' => $token
        ]);
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        //✅ احذف فقط توكن الجهاز الحالي (لا تحذف كل التوكنات)
        if ($request->filled('fcm_token')) {
            Device_tokens::where('user_id', $user->id)
                ->where('token', $request->fcm_token)
                ->delete();
        }
        // احذف access token الخاص بالمصادقة
        $user->currentAccessToken()->delete();
        return response()->json([
            'رسالة' => 'تم تسجيل الخروج بنجاح'
        ]);
    }
    public function forgetPassword1(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255'
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'رسالة' => '  هذا الحساب غير موجود لدينا أي لم تقم من قبل بالتسجيل في تطبيقنا عبر هذا الحساب . قد تكون سجلت في تطبيقنا عبر حساب اخر'
            ]);
        }
        $code = mt_rand(0000, 9999);
        $user->update([
            'code' => $code
        ]);
        Mail::to($user->email)->send(new Forgetpassword($user));
        return response()->json([
            'رسالة' => 'لقد قمنا بارسال كود على ايميلك يجب عليك ادخاله لدينا لنتأكد أنك صاحب الايميل . ملاحظة:قد يستغرق وصول الكود بعض الوقت'
        ]);
    }
    public function forgetPassword2(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'code' => 'required|integer',
            'password' => 'required|string|min:8|max:255|confirmed'
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'رسالة' => 'لقد قمت بادخال ايميل غير صحيح',
            ]);
        }
        $user->update([
            'password' => $request->password
        ]);
        return response()->json([
            'رسالة' => 'تم تغيير كلمة السر بنجاح',
            'user' => $user
        ]);
    }
    public function registerForAdmin(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|string|max:45',
            'email' => 'required|string|email|max:45|unique:users,email',
            'phone_number' => 'required|string|max:15',
            'password' => 'required|string|min:8|max:255|confirmed',
            'country' => 'required|string',
            'city' => 'required|string',
            'numberOfPointsAllowed' => 'required|integer',

            // 👇 Validation للأصناف
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        // تحديد الرول
        $validateData['role'] = 'admin';

        // إنشاء المستخدم أولاً
        $user = User::create($validateData);

        // ربط الأصناف (إن وجدت)
        if (!empty($validateData['categories'])) {
            foreach ($validateData['categories'] as $categoryId) {
                DB::table('category_user')->insert([
                    'user_id' => $user->id,
                    'category_id' => $categoryId
                ]);
            }
        }

        return response()->json([
            'رسالة' => 'تم انشاءالحساب',
            'حسابك' => $user
        ],);
    }
    public function login2(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:45',
            'password=>requird|string|max:255|'
        ]);

        // ✳️ محاولة تسجيل الدخول
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'رسالة' => 'يوجد خطأ في البيانات'
            ], 401);
        }
        $user = User::where('email', $request->email)->first();
        if ($user->role != 'admin') {
            return response()->json([
                'message' => 'لا يمكنك التسجيل والوصول والمتابعة الى داخل هذا التطبيق'
            ]);
        }
        $token = $user->createToken('mobile')->plainTextToken;

        // ✅ خزّن التوكن الجديد (لا نحذف السابقين)
        if ($request->filled('fcmToken')) {
            Device_tokens::updateOrCreate(
                ['token' => $request->fcmToken],
                ['user_id' => $user->id, 'platform' => $request->platform]
            );
        }

        return response()->json([
            'رسالة' => 'تمت عملية تسجيل الدخول بنجاح',
            'user' => $user,
            'token' => $token
        ]);
    }
    public function profile1()
    {
        $user = Auth::User();
        return response()->json([
            'user' => $user
        ]);
    }

    public function profile2()
    {
        $admin = Auth::User();
        return response()->json([
            'admin' => $admin
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validateDate = $request->validate([
            'name' => 'string|max:45',
            'email' => 'string|email|max:45|unique:users,email',
            'phone_number' => 'string|max:15',
            'country' => 'string',
            'city' => 'string',
            'password' => 'string|min:8|max:255|confirmed',

        ]);
        $user = User::find(Auth::id());
        $user->update($validateDate);
        return response()->json([
            'message' => 'تم تحديث ملفك الشخصي بنجاح'
        ]);
    }
    public function showAlladmins()
    {
        $admins = User::where('role', 'admin')->get();
        return response()->json([
            'admins' => $admins
        ]);
    }
    public function countryUsers()
    {
        $user = User::find(Auth::id());
        $users = DB::table('users')
            ->where('country', $user->country)
            ->where('role', 'user')
            ->orderBy('points', 'desc')
            ->select(
                'id',
                'name',
                'phone_number',
                'country',
                'city',
                'points',
            )
            ->paginate(10);
        return response()->json([
            'users' => $users
        ]);
    }
    public function allUsers()
    {
        $users = DB::table('users')
            ->where('role', 'user')
            ->orderBy('points_total', 'desc')
            ->select(
                'id',
                'name',
                'phone_number',
                'country',
                'city',
                'points',
                'points_total',
                'numberOfScan'
            )
            ->get();
        return response()->json([
            'users' => $users
        ]);
    }
    public function classificationAdminsByCountry(Request $request)
    {
        $request->validate([
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'
        ]);
        $admins = User::where('role', 'admin')->where('country', $request->country)->get();
        return response()->json([
            'admins' => $admins
        ]);
    }

    public function classificationUsersByCountry(Request $request)
    {
        $request->validate([
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'
        ]);
        //$users=User::where('role','user')->where('country',$request->country)->get();
        $users = DB::table('users')
            ->where('role', 'user')
            ->where('country', $request->country)
            ->orderBy('points_total', 'desc')
            ->select(
                'id',
                'name',
                'phone_number',
                'country',
                'city',
                'points',
                'points_total',
                'numberOfScan'
            )
            ->get();
        return response()->json([
            'users' => $users
        ]);
    }

    public function classificationAdminsByCountryAndCategory(Request $request, $id)
    {
        $request->validate([
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'
        ]);
        $category = Category::find($id);
        $admins = $category->users()->where('country', $request->country)->where('role', 'admin')->get();
        return response()->json([
            'admins' => $admins
        ]);
    }

    public function classificationUsersByCountryAndCategory(Request $request, $id)
    {
        $request->validate([
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda'
        ]);
        $category = Category::find($id);
        $users = $category->users()
            ->where('country', $request->country)
            ->where('role', 'user')
            ->orderBy('category_user.categoricalUserPoints', 'desc')
            ->get();
        return response()->json([
            'users' => $users
        ]);
    }
    public function increasePointsForAll(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1|max:100'
        ], [
            'points.required' => 'حقل النقاط مطلوب',
            'points.integer' => 'يجب أن تكون النقاط رقمًا صحيحًا',
            'points.min' => 'يجب ألا تقل النقاط عن 1',
            'points.max' => 'يجب ألا تزيد النقاط عن 100',
        ]);

        DB::beginTransaction();

        try {
            // زيادة النقاط
            User::where('role', 'user')->increment('points', $validated['points']);
            User::where('role', 'user')->increment('points_total', $validated['points']);

            DB::commit();

            return response()->json([
                'message' => 'تمت زيادة النقاط لجميع الزبائن',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ حاول مجددا  ',
                'status' => 'error'
            ]);
        }
    }
    public function increasePointsAccordingToCountry(Request $request)
    {
        $validated = $request->validate([
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda',
            'points' => 'required|integer|min:1|max:100',
        ], [
            'points.required' => 'حقل النقاط مطلوب',
            'points.integer' => 'يجب أن تكون النقاط رقمًا صحيحًا',
            'points.min' => 'يجب ألا تقل النقاط عن 1',
            'points.max' => 'يجب ألا تزيد النقاط عن 100',
        ]);

        DB::beginTransaction();

        try {
            // تحديث آمن باستخدام increment
            $updated = User::where('country', $validated['country'])
                ->where('role', 'user')
                ->increment('points', $validated['points']);

            User::where('country', $validated['country'])
                ->where('role', 'user')
                ->increment('points_total', $validated['points']);

            if ($updated === 0) {
                DB::commit();
                return response()->json([
                    'message' => 'لا يوجد زبائن في هذه المحافظة',
                    'status' => 'error'
                ]);
            }
            DB::commit();

            return response()->json([
                'message' => 'تم تزويد النقاط لزبائن هذه المحافظة بنجاح',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ حاول مجددا  ',
                'status' => 'error'
            ]);
        }
    }

    public function increasePointsAccordingToCategory(Request $request, $id)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1|max:100'
        ], [
            'points.required' => 'حقل النقاط مطلوب',
            'points.integer' => 'يجب أن تكون النقاط رقمًا صحيحًا',
            'points.min' => 'يجب ألا تقل النقاط عن 1',
            'points.max' => 'يجب ألا تزيد النقاط عن 100',
        ]);
        $points = $validated['points'];
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'الفئة غير موجودة',
                'status' => 'error'
            ]);
        }
        $hasUsers = DB::table('category_user')
            ->where('category_id', $category->id)
            ->exists();

        if (!$hasUsers) {
            return response()->json([
                'message' => 'لا يوجد زبائن في هذا الاختصاص',
                'status' => 'error'
            ]);
        }

        DB::beginTransaction();

        try {
            // تحديث users بدون تحميل IDs
            $updated = DB::table('users')
                ->where('role', 'user')
                ->whereIn('id', function ($query) use ($category) {
                    $query->select('user_id')
                        ->from('category_user')
                        ->where('category_id', $category->id);
                })
                ->increment('points', $points);

            DB::table('users')
                ->where('role', 'user')
                ->whereIn('id', function ($query) use ($category) {
                    $query->select('user_id')
                        ->from('category_user')
                        ->where('category_id', $category->id);
                })
                ->increment('points_total', $points);

            // تحديث pivot table
            DB::table('category_user')
                ->where('category_id', $category->id)
                ->increment('categoricalUserPoints', $points);

            DB::commit();

            return response()->json([
                'message' => 'تم تزويد النقاط لزبائن هذه الفئة',
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

    public function increasePointsAccordingToCountryAndCategory(Request $request, $id)
    {
        $validated = $request->validate([
            'country' => 'required|in:Damascus,Rif dimashq,Aleppo,Homs,Hama,Latakia,Tartus,Daraa,Quneitra,Idlib,Raqqa,Deir ez-Zor,Hasakah,As-Suwayda',
            'points' => 'required|integer|min:1|max:100'

        ], [
            'points.required' => 'حقل النقاط مطلوب',
            'points.integer' => 'يجب أن تكون النقاط رقمًا صحيحًا',
            'points.min' => 'يجب ألا تقل النقاط عن 1',
            'points.max' => 'يجب ألا تزيد النقاط عن 100',
        ]);
        $points = $validated['points'];
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'الفئة غير موجودة',
                'status' => 'error'
            ]);
        }
        $hasUsers = DB::table('users')
            ->where('role', 'user')
            ->where('country', $validated['country'])
            ->whereIn('id', function ($query) use ($category) {
                $query->select('user_id')
                    ->from('category_user')
                    ->where('category_id', $category->id);
            })
            ->exists();

        if (!$hasUsers) {
            return response()->json([
                'message' => 'لا يوجد زبائن في هذا الاختصاص ضمن هذه المحافظة',
                'status' => 'error'
            ]);
        }
        DB::beginTransaction();
        try {
            // تحديث users بدون تحميل IDs
            DB::table('users')
                ->where('role', 'user')
                ->where('country', $validated['country'])
                ->whereIn('id', function ($query) use ($category) {
                    $query->select('user_id')
                        ->from('category_user')
                        ->where('category_id', $category->id);
                })
                ->increment('points', $points);

            DB::table('users')
                ->where('role', 'user')
                ->where('country', $validated['country'])
                ->whereIn('id', function ($query) use ($category) {
                    $query->select('user_id')
                        ->from('category_user')
                        ->where('category_id', $category->id);
                })
                ->increment('points_total', $points);

            // تحديث pivot table
            DB::table('category_user')
                ->where('category_id', $category->id)
                ->whereIn('user_id', function ($query) use ($category, $validated) {
                    $query->select('id')
                        ->from('users')
                        ->where('role', 'user')
                        ->where('country', $validated['country'])
                        ->whereIn('id', function ($q) use ($category) {
                            $q->select('user_id')
                                ->from('category_user')
                                ->where('category_id', $category->id);
                        });
                })
                ->increment('categoricalUserPoints', $points);

            DB::commit();

            return response()->json([
                'message' => 'تم تزويد النقاط لزبائن هذه الفئة في هذه المحافظة بنجاح',
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


    public function showCountriesAccordingToTheTotalPointsOfThereUsers()
    {
        $countries = DB::table('users')
            ->select('country', DB::raw('SUM(points_total) as total_points'))
            ->groupBy('country')
            ->orderByDesc('total_points')
            ->get();

        return response()->json([
            'المحافظات حسب مجموع نقاط مستخدميها' => $countries
        ]);
    }
    public function showCategoriesAccordingToTheCategoricalUserPoints()
    {
        $categories = Category::select(
            'categories.id',
            'categories.name'
        )
            ->selectRaw('SUM(category_user.categoricalUserPoints) as total_points')
            ->join('category_user', 'categories.id', '=', 'category_user.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_points')
            ->get();

        return response()->json([
            'الفئات حسب مجموع نقاط مستخدميها' => $categories
        ]);
    }
    public function showCountriesAndCategoriesAccordingToTheCategoricalUserPoints()
    {
        $results = DB::table('category_user')
            ->join('users', 'category_user.user_id', '=', 'users.id')
            ->join('categories', 'category_user.category_id', '=', 'categories.id')
            ->select(
                'categories.name as category',
                'users.country as country',
                DB::raw('SUM(category_user.categoricalUserPoints) as total_points')
            )
            ->groupBy('categories.name', 'users.country')
            ->orderByDesc('total_points')
            ->get();

        return response()->json($results);
    }
    public function block($id)
    {
        $person = User::findOrFail($id);
        if ($person->role == 'admin') {
            $person->update([
                'role' => 'blocked_admin'
            ]);
            return response()->json([
                'message' => 'تم حظر هذا الوكيل بنجاح'
            ]);
        }
        $person->update([
            'role' => 'blocked_user'
        ]);
        return response()->json([
            'message' => 'تم حظر هذا الزبون بنجاح'
        ]);
    }
    public function unBlock($id)
    {
        $person = User::findOrFail($id);
        if ($person->role == 'blocked_admin') {
            $person->update([
                'role' => 'admin'
            ]);
            return response()->json([
                'message' => 'تم فك الحظر عن هذا الوكيل بنجاح'
            ]);
        }
        $person->update([
            'role' => 'user'
        ]);
        return response()->json([
            'message' => 'تم فك الحظر عن هذا الزبون بنجاح'
        ]);
    }

    public function showBlockedUser()
    {
        $users = DB::table('users')
            ->where('role', 'blocked_user')
            ->orderBy('id', 'desc')
            ->select(
                'id',
                'name',
                'phone_number',
                'country',
                'city',
                'points',
                'points_total',
                'numberOfScan'
            )
            ->get();
        return response()->json([
            'users' => $users
        ]);
    }
    public function showBlockedAdmin()
    {
        $admins = DB::table('users')
            ->where('role', 'blocked_admin')
            ->orderBy('id', 'desc')
            ->select(
                'id',
                'name',
                'phone_number',
                'country',
                'city',
                'numberOfPointsAllowed',
                'pointsConsumed',
                'numberOfPrint'
            )
            ->get();
        return response()->json([
            'admins' => $admins
        ]);
    }

    public function myCategory_admin()
    {
        $user = User::find(Auth::id());
        $categories = $user->categories;
        return response()->json([
            'فئاتي' => $categories
        ]);
    }

    public function increasePointsByAdmin(Request $request, $idOfUser, $idOfCategory)
    {
        if ($request->points < 0) {
            return response()->json([
                'message' => 'لا يمكن ادخال قيمة سالبة'
            ]);
        }
        $request->validate([
            'points' => 'required|integer'
        ]);

        DB::beginTransaction();
        try {
            // 🔒 اقفل صف الأدمن         
            $admin = User::where('id', Auth::id())->lockForUpdate()->first();
            //👉 لو شلت lockForUpdate() — أنت رجّعت ثغرة خطيرة (Race Condition)
            $adding = $request->points;

            if ($admin->numberOfPointsAllowed < $admin->pointsConsumed + $adding) {
                DB::rollBack();
                return response()->json([
                    'message' => '❌لا يمكن القيام بالعملية لأنك ستتجاوز حد النقاط المسموح لك بها❌',
                    'status' => 'error'
                ]);
            }


            $user = User::lockForUpdate()->findOrFail($idOfUser);
            // تحديث المستخدم
            $user->increment('points', $adding);
            $user->increment('points_total', $adding);
            $user->increment('numberOfNotifications');


            // تحديث نقاط الأدمن
            $admin->update([
                'pointsConsumed' => $admin->pointsConsumed + $adding
            ]);

            Notificationn::create([
                'title' => "{$user->name}",
                'body' => " قام الوكيل {$admin->name} بتزويد {$adding} نقاط لك ليصبح عدد نقاطك {$user->points}",
                'user_id' => $idOfUser
            ]);

            Register::create([
                'register' => " قام الوكيل {$admin->name} بتزويد {$adding} نقطة ل {$user->name} ليصبح عدد نقاطه {$user->points}",
                'type' => 'increase'
            ]);

            // جلب السجل
            $category_user = DB::table('category_user')
                ->where('user_id', $idOfUser)
                ->where('category_id', $idOfCategory)
                ->lockForUpdate() // مهم لمنع race condition
                ->first();

            if (!$category_user) {
                DB::table('category_user')->insert([
                    'user_id' => $idOfUser,
                    'category_id' => $idOfCategory,
                    'categoricalUserPoints' => $adding,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('category_user')
                    ->where('user_id', $idOfUser)
                    ->where('category_id', $idOfCategory)
                    ->update([
                        'categoricalUserPoints' => DB::raw('categoricalUserPoints + ' . (int)$adding),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'تم تزويد النقاط بنجاح',
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
    public function superAdminRegisterrr(Request $request)
    {
        $validateDate = $request->validate([
            'name' => 'required|string|max:45',
            'email' => 'required|string|email|max:45|unique:users,email',
            'phone_number' => 'required|string|max:15',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        $validateDate['role'] = 'super_admin';
        $validateDate['country'] = 'Daraa';
        $validateDate['city'] = 'ابطع';

        $user = User::create($validateDate);

        return response()->json([
            'رسالة' => 'تم انشاء حساب لك والخطوة التالية عليك أن تقوم بتسجيل الدخول عبر ايميل وكلمة سر هذا الحساب',
            'حسابك' => $user
        ]);
    }
    function superAdminLoginnn(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:45',
            'password=>required|string|max:255|'
        ]);
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'رسالة' => 'يوجد خطأ في الايميل أو كلمة سر',
            ]);
        }
        $user = User::where('email', $request->email)->first();
        if ($user->role != 'super_admin') {
            return response()->json([
                'message' => 'لا يمكنك التسجيل والوصول والمتابعة الى داخل هذا التطبيق'
            ]);
        }
        $token = $user->createToken('mobile')->plainTextToken;
        return response()->json([
            'رسالة' => 'تمت عملية تسجيل الدخول بنجاح',
            'user' => $user,
            'token' => $token
        ]);
    }
}
