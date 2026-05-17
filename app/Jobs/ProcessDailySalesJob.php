<?php
namespace App\Jobs;

use App\Models\Order;
use App\Models\DailySalesReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
class ProcessDailySalesJob implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue, Queueable, SerializesModels,Batchable;

    public $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function handle()
    {
        $totalSales = 0;
        $totalProfit = 0;
        $ordersCount = 0;

        Order::with('items')
            ->whereDate('created_at', $this->date)
            ->chunk(200, function ($orders) use (&$totalSales, &$totalProfit, &$ordersCount) {

                foreach ($orders as $order) {
                    $ordersCount++;

                    foreach ($order->items as $item) {
                        $totalSales += $item->price * $item->item_quantity;
                        $totalProfit += $item->profit * $item->item_quantity;
                    }
                }
            });

        DailySalesReport::updateOrCreate(
            ['date' => $this->date],
            [
                'total_sales' => $totalSales,
                'total_profit' => $totalProfit,
                'orders_count' => $ordersCount,
            ]
        );
    }
}