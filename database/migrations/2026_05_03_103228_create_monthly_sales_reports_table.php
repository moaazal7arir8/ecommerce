<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_sales_reports', function ($table) {
            $table->id();
            $table->date('generated_at');
            $table->decimal('total_sales', 30, 2);
            $table->decimal('total_profit', 30, 2);
            $table->integer('days_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_sales_reports');
    }
};
