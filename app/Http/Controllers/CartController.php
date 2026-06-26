<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\CartItem;
use Illuminate\Support\facades\Auth;

class CartController extends Controller
{
    public function createCart(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();

        // 1. جلب السلة الحالية للمستخدم أو إنشاء واحدة جديدة إذا لم تكن موجودة
        $cart = Cart::firstOrCreate([
            'user_id' => $userId
        ]);

        // 2. إضافة المنتجات أو تحديث كميتها إذا كانت موجودة بالفعل في السلة
        foreach ($request->items as $item) {

            // البحث عن المنتج داخل هذه السلة بالتحديد
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $item['product_id'])
                ->first();

            if ($cartItem) {
                // إذا كان المنتج موجوداً مسبقاً، قم بزيادة الكمية
                $cartItem->increment('item_quantity', $item['quantity']);
            } else {
                // إذا كان المنتج جديداً على السلة، قم بإنشائه
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $item['product_id'],
                    'item_quantity' => $item['quantity'],
                ]);
            }
        }

        return response()->json([
            'message' => 'تم تحديث السلة بنجاح',
            'cart_id' => $cart->id // إرجاع رقم السلة للتأكيد
        ]);
    }
    public function deleteCart()
    {
        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart) {
            return response()->json([
                'message' => 'السلة حذفت بالفعل'
            ], 404);
        }
        $cart->delete();

        return response()->json([
            'message' => 'تم حذف السلة بنجاح'
        ]);
    }
    public function showUserCarts()
    {
        $carts = Cart::with('items.product')
            ->where('user_id', Auth::id())
            ->paginate(10);

        return response()->json($carts);
    }
}
