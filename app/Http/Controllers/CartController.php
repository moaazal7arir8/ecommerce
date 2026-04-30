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

        $cart = Cart::create([
            'user_id' => Auth::id(),
        ]);

        foreach ($request->items as $item) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $item['product_id'],
                'item_quantity' => $item['quantity'],
            ]);
        }
        return response()->json([
            'message' => 'تم إنشاء السلة بنجاح',

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
