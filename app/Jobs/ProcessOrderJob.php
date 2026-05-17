<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DeviceToken;
use App\Models\Notification as NotificationModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cartId;
    protected $userId;
    protected $address;

    public function __construct($cartId, $userId, $address)
    {
        $this->cartId = $cartId;
        $this->userId = $userId;
        $this->address = $address;
    }

    public function handle()
    {
        DB::beginTransaction();

        $user = User::find($this->userId);
        try {
            $total = 0;

            // $tokens = DeviceToken::where('user_id', $this->userId)
            //     ->pluck('token')
            //     ->toArray();

            // if (empty($tokens)) {
            //     DB::rollBack();
            //     return;
            // }

            $cart = Cart::where('id', $this->cartId)->first();
            if (!$cart || $cart->items->isEmpty()) {
                DB::rollBack();
                return;
            }

            $items = $cart->items->sortBy('product_id');

            $productIds = $items->pluck('product_id')->unique();

            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {

                $product = $products[$item->product_id];


                if (!$product) {

                    DB::rollBack();

                    // $message = CloudMessage::new()
                    //     ->withNotification(
                    //         FirebaseNotification::create(
                    //             'فشل الطلب',
                    //             'لأن أحد المنتجات لم تعد متوفرة'
                    //         )
                    //     );

                    // Firebase::messaging()->sendMulticast($message, $tokens);

                    NotificationModel::create([
                        'title' => 'فشل الطلب',
                        'body' =>  'لأن أحد المنتجات لم تعد متوفرة',
                        'user_id' => $this->userId
                    ]);

                    $user->increment('numberOfNotifications');

                    return;
                }

                if ($product->quantity < $item->item_quantity) {
                    DB::rollBack();

                    // $message = CloudMessage::new()
                    //     ->withNotification(
                    //         FirebaseNotification::create(
                    //             'فشل الطلب',
                    //             'لأن أحد المنتجات لم تعد متوفرة'
                    //         )
                    //     );

                    // Firebase::messaging()->sendMulticast($message, $tokens);

                    NotificationModel::create([
                        'title' => 'فشل الطلب',
                        'body' =>  'لأن أحد المنتجات غير متوفرة بالكمية التي طلبتها  ',
                        'user_id' => $this->userId
                    ]);

                    $user->increment('numberOfNotifications');
                    return;
                }

                $total += $product->price * $item->item_quantity;
            }

            if ($user->wallet < $total) {
                DB::rollBack();
                // $message = CloudMessage::new()
                //     ->withNotification(
                //         FirebaseNotification::create(
                //             'فشل الطلب',
                //             'النقود في محفظتك لا تكفي لشراء الطلب'
                //         )
                //     );

                // Firebase::messaging()->sendMulticast($message, $tokens);

                NotificationModel::create([
                    'title' => 'فشل الطلب',
                    'body' =>  'النقود في محفظتك لا تكفي لشراء الطلب',
                    'user_id' => $this->userId
                ]);
                $user->increment('numberOfNotifications');
                return;
            }
            $user->decrement('wallet', $total);
            $order = Order::create([
                'user_id' => $this->userId,
                'status' => 'pending',
                'address' => $this->address
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

                $product->decrement('quantity', $item->item_quantity);

                // Product::where('id', $item->product_id)
                //     ->decrement('quantity', $item->item_quantity);
            }

            DB::commit();

            // $message = CloudMessage::new()
            //     ->withNotification(
            //         FirebaseNotification::create(
            //             'تم قبول طلبك',
            //             'وجاري اكمال معالجته'
            //         )
            //     );

            // Firebase::messaging()->sendMulticast($message, $tokens);

            NotificationModel::create([
                'title' => 'تم قبول طلبك',
                'body' =>  'وجاري اكمال معالجته',
                'user_id' => $this->userId
            ]);

            $user->increment('numberOfNotifications');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Job Failed: ' . $e->getMessage());
        }
    }
}
