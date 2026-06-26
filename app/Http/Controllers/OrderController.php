<?php

namespace App\Http\Controllers;


use App\Models\DeviceToken;
use App\Models\Notification as NotificationModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\facades\Auth;
use App\Jobs\ProcessOrderJob;

class OrderController extends Controller
{

    public function createOrder2(Request $request)
    {
        $validateData = $request->validate([
            'address' => 'required|string'
        ]);

        $cart = Cart::with('items')
            ->where('user_id', Auth::id())
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        ProcessOrderJob::dispatch(
            $cart->id,
            Auth::id(),
            $validateData['address']
        );

        return response()->json([
            'message' => 'جار معالجة طلبك',
            'server' => env('APP_NAME')

        ]);
    }
    public function createOrder1(Request $request)
    {
        $validateData = $request->validate([
            'address' => 'required|string'
        ]);

        $user = User::find(Auth::id());

        $cart = Cart::with('items')
            ->where('user_id', $user->id)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        $total = 0;
        $items = $cart->items;

        $productIds = $items->pluck('product_id')->unique();

        $products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {

            $product = $products[$item->product_id] ?? null;

            if (!$product) {
                return response()->json(
                    ['message' => 'أحد المنتجات غير متوفرة'],
                    400
                );
            }

            if ($product->quantity < $item->item_quantity) {

                return response()->json(
                    ['message' => 'أحد المنتجات غير متوفرة بالكمية المطلوبة'],
                    400
                );
            }
            $total += $product->price * $item->item_quantity;
        }

        if ($user->wallet < $total) {

            return response()->json([
                'message' => 'النقود في محفظتك لا تكفي لشراء الطلب'
            ]);
        }
        $user->update([
            'wallet' => $user->wallet - $total
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'address' => $validateData['address']
        ]);

        foreach ($items as $item) {

            $product = $products[$item->product_id];

            OrderItem::create([
                'price' => $product->price,
                'profit' => $product->profit,
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'item_quantity' => $item->item_quantity,
            ]);

            $product->update([
                'quantity' => $product->quantity - $item->item_quantity
            ]);
        }
        // $cart->items()->delete();

        return response()->json([
            'message' => 'تم قيول طلبك بنجاح',
            'order_id' => $order->id
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
