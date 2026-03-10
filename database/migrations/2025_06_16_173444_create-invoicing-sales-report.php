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
        Schema::create('invoicing_sales_report', function (Blueprint $table) {
            $table->id();
            $table->string('frequency')->nullable();            
            $table->date('range_sdate')->nullable();
            $table->date('range_edate')->nullable();

            $table->boolean('credit_card')->nullable();
            $table->boolean('cash')->nullable();
            $table->boolean('transfer')->nullable();
            $table->boolean('paypal')->nullable();
            $table->boolean('venmo')->nullable();
            $table->boolean('cashapp')->nullable();
            $table->boolean('crypto')->nullable();
            $table->boolean('sales_totals')->nullable();
            $table->boolean('sales_detail')->nullable();
            $table->boolean('profit')->nullable();
            $table->boolean('margin')->nullable();
            $table->boolean('sales_tax')->nullable();

            $table->string('email')->nullable();
            $table->boolean("deleted")->default(false);
            $table->datetime("reported_date")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoicing_sales_report');
    }
};
