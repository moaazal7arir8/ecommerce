<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Support\facades\Auth;

class OrderController extends Controller
{
    public function createOrder(Request $request, $id)
    {
        $validateData = $request->validate([
            'address' => 'required|string'
        ]);
        $cart = Cart::with('items.product')
            ->where('id', $id)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        $totalPrice = 0;
        $profits = 0;
        foreach ($cart->items as $item) {
            $product = $item->product;

            if (!$product) {
                return response()->json([
                    'message' => 'بطلبك هناك منتج غير موجود أعد المحاولة من جديد'
                ], 404);
            }

            if ($item->item_quantity <= 0) {
                return response()->json([
                    'message' => 'المنتج التالي اانتهت كميته لدينا' . $product->name
                ], 400);
            }

            if ($product->quantity < $item->item_quantity) {
                return response()->json([
                    'message' => 'لا يتوفر لدينا الكمية الكافيه من المنتج التالي ' . $product->name
                ], 400);
            }
            $price = $item->product->price ?? 0;
            $totalPrice += $price * $item->item_quantity;

            $profit = $item->product->profit ?? 0;
            $profits += $profit * $item->item_quantity;
        }

        $order = Order::create([
            'user_id' => Auth::id(),
            'total_price' => $totalPrice,
            'profits' => $profits,
            'status' => 'pending',
            'address' => $validateData['address']
        ]);
        foreach ($cart->items as $item) {
            OrderItem::create([
                'price' => $item->product->price,
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'item_quantity' => $item->item_quantity,
            ]);
            $item->product->decrement('quantity', $item->item_quantity);
        }
        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order
        ]);
    }
    public function showUserOrders()
    {
        $orders = Order::with('items.product')
            ->where('user_id', Auth::id())
            ->paginate(10);

        return response()->json($orders);
    }
}
