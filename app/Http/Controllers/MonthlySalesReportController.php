<?php

namespace App\Http\Controllers;


use App\Jobs\ProcessDailySalesJob;
use App\Jobs\ProcessMonthlySalesJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use App\Models\Order;
class MonthlySalesReportController extends Controller
{
    public function runMonthlyInventory2()
    {
        $start = Carbon::now()->startOfMonth()->copy();
        $end = Carbon::today();

        $processedDates = DailySalesReport::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString()
        ])->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $jobs = [];

        while ($start->lte($end)) {
            $currentDate = $start->toDateString();

            if (! in_array($currentDate, $processedDates)) {
                $jobs[] = new ProcessDailySalesJob($currentDate);
            }

            $start->addDay();
        }

        if (empty($jobs)) {
            ProcessMonthlySalesJob::dispatch(
                now()->startOfMonth()->toDateString(),
                $end->toDateString()
            );

            return response()->json([
                'message' => 'All daily reports already processed. Monthly report refreshed.'
            ]);
        }

        Bus::batch($jobs)
            ->then(function ($batch) use ($end) {
                ProcessMonthlySalesJob::dispatch(
                    now()->startOfMonth()->toDateString(),
                    $end->toDateString()
                );
            })
            ->dispatch();

        return response()->json([
            'message' => 'بدأت عملية الجرد وسيصلك إشعار عند الإكتمال'
        ]);
    }
    // public function runMonthlyInventory1()
    // {
    //     $start = Carbon::now()->startOfMonth();
    //     $end = Carbon::today();

    //     $processedDates = DailySalesReport::whereBetween('date', [
    //         $start->toDateString(),
    //         $end->toDateString()
    //     ])->pluck('date')
    //         ->map(fn($date) => Carbon::parse($date)->toDateString())
    //         ->toArray();

    //     $current = $start->copy();
    //     $createdReports = [];

    //     while ($current->lte($end)) {

    //         $date = $current->toDateString();

    //         if (!in_array($date, $processedDates)) {

    //             $totalSales = 0;
    //             $totalProfit = 0;
    //             $ordersCount = 0;

    //             $orders = Order::with('items')
    //                 ->whereDate('created_at', $date)
    //                 ->get();

    //             foreach ($orders as $order) {
    //                 $ordersCount++;

    //                 foreach ($order->items as $item) {
    //                     $totalSales += $item->price * $item->item_quantity;
    //                     $totalProfit += $item->profit * $item->item_quantity;
    //                 }
    //             }

    //             DailySalesReport::updateOrCreate(
    //                 ['date' => $date],
    //                 [
    //                     'total_sales' => $totalSales,
    //                     'total_profit' => $totalProfit,
    //                     'orders_count' => $ordersCount,
    //                 ]
    //             );

    //             $createdReports[] = $date;
    //         }

    //         $current->addDay();
    //     }

    //     $daily = DailySalesReport::whereBetween('date', [
    //         $start->toDateString(),
    //         $end->toDateString()
    //     ])->get();

    //     $monthlySalesReport=MonthlySalesReport::updateOrCreate(
    //         [
    //             'generated_at' => $start->toDateString(),
    //         ],
    //         [
    //             'total_sales' => $daily->sum('total_sales'),
    //             'total_profit' => $daily->sum('total_profit'),
    //             'days_count' => 16,
    //         ]
    //     );

    //     return response()->json([
    //         'message' => 'تمت عمليت الجرد بنجاح',
    //         'processed_days' => $monthlySalesReport
    //     ]);
    // }
    public function runMonthlyInventory1()
{
    $startTracking = microtime(true);

    $start = Carbon::now()->startOfMonth();
    $end = Carbon::today();

    $processedDates = DailySalesReport::whereBetween('date', [
        $start->toDateString(),
        $end->toDateString()
    ])->pluck('date')
        ->map(fn($date) => Carbon::parse($date)->toDateString())
        ->toArray();

    $current = $start->copy();
    $createdReports = [];

    while ($current->lte($end)) {

        $date = $current->toDateString();

        if (!in_array($date, $processedDates)) {

            $totalSales = 0;
            $totalProfit = 0;
            $ordersCount = 0;

            $orders = Order::with('items')
                ->whereDate('created_at', $date)
                ->get();

            foreach ($orders as $order) {
                $ordersCount++;

                foreach ($order->items as $item) {
                    $totalSales += $item->price * $item->item_quantity;
                    $totalProfit += $item->profit * $item->item_quantity;
                }
            }

            DailySalesReport::updateOrCreate(
                ['date' => $date],
                [
                    'total_sales' => $totalSales,
                    'total_profit' => $totalProfit,
                    'orders_count' => $ordersCount,
                ]
            );

            $createdReports[] = $date;
        }

        $current->addDay();
    }

    $daily = DailySalesReport::whereBetween('date', [
        $start->toDateString(),
        $end->toDateString()
    ])->get();

    $monthlySalesReport = MonthlySalesReport::updateOrCreate(
        [
            'generated_at' => $start->toDateString(),
        ],
        [
            'total_sales' => $daily->sum('total_sales'),
            'total_profit' => $daily->sum('total_profit'),
            'days_count' => 16,
        ]
    );

    // =========================
    // 🔥 RAM METRICS
    // =========================

    $ramUsedMB = memory_get_usage(true) / 1024 / 1024;
    $ramPeakMB = memory_get_peak_usage(true) / 1024 / 1024;

    $totalRamMB = null;

    @exec(
        'powershell -command "(Get-CimInstance Win32_ComputerSystem).TotalPhysicalMemory / 1MB"',
        $ramOut
    );

    if (!empty($ramOut[0])) {
        $totalRamMB = (float) $ramOut[0];
    }

    $ramPercent = $totalRamMB
        ? round(($ramUsedMB / $totalRamMB) * 100, 2)
        : null;

    // =========================
    // 🔥 CPU METRICS (FIXED)
    // =========================

    $cpuPercent = null;

    @exec(
        'powershell -command "$p = Get-Process httpd; $cpu = ($p | Measure-Object CPU -Sum).Sum; $cores = (Get-CimInstance Win32_ComputerSystem).NumberOfLogicalProcessors; [math]::Round(($cpu / $cores), 2)"',
        $cpuOut
    );

    if (!empty($cpuOut[0])) {
        $cpuPercent = (float) $cpuOut[0];
    }

    $executionTime = microtime(true) - $startTracking;

    // =========================
    // 🔥 OUTPUT (CLI)
    // =========================

    echo "=============================\n";
    echo "📊 API PERFORMANCE REPORT\n";
    echo "=============================\n";
    echo "RAM Used: " . round($ramUsedMB, 2) . " MB\n";
    echo "RAM Peak: " . round($ramPeakMB, 2) . " MB\n";
    echo "RAM Usage: " . ($ramPercent ?? 'N/A') . "%\n";
    echo "CPU (Apache): " . ($cpuPercent ?? 'N/A') . "%\n";
    echo "Execution Time: " . round($executionTime, 2) . " sec\n";
    echo "=============================\n";

    return response()->json([
        'message' => 'تمت عمليت الجرد بنجاح',
        'processed_days' => $monthlySalesReport,
        'performance' => [
            'ram_used_mb' => round($ramUsedMB, 2),
            'ram_peak_mb' => round($ramPeakMB, 2),
            'ram_usage_percent' => $ramPercent,
            'cpu_apache_percent' => $cpuPercent,
            'execution_time_sec' => round($executionTime, 2),
        ]
    ]);
}
    }
