<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySalesReport extends Model
{
    protected $fillable = ['generated_at','total_sales','total_profit','days_count'];
}