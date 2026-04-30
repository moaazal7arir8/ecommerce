<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{


    public function downloadMonthlyReport()
    {
        $totalSales = 0;
        $totalProfit = 0;
        $totalOrders = 0;

        Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->chunk(500, function ($orders) use (&$totalSales, &$totalProfit, &$totalOrders) {

                foreach ($orders as $order) {
                    $totalSales += $order->total_price;
                    $totalProfit += $order->profits;
                    $totalOrders++;
                }
            });

        $report = [
            'total_sales' => $totalSales,
            'total_profits' => $totalProfit,
            'total_orders' => $totalOrders,
            'month' => now()->format('F Y'),
        ];

        $pdf = Pdf::loadView('reports.monthly', compact('report'));

        return $pdf->download('monthly_report_' . now()->format('Y_m') . '.pdf');
    }
}
