<?php

namespace App\Jobs;

use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMonthlySalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $startDate;
    public $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function handle()
    {
        $daily = DailySalesReport::whereBetween('date', [
            $this->startDate,
            $this->endDate
        ])->get();

        MonthlySalesReport::updateOrCreate(
            [
                'generated_at' => Carbon::parse($this->startDate)->startOfMonth()->toDateString(),
            ],
            [
                'total_sales' => $daily->sum('total_sales'),
                'total_profit' => $daily->sum('total_profit'),
                'days_count' => $daily->count(),
            ]
        );
    }
}