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
        Schema::create('customer_service_report', function (Blueprint $table) {
            $table->id();
            $table->string('frequency')->nullable();            
            $table->date('range_sdate')->nullable();
            $table->date('range_edate')->nullable();
            $table->date('arrive_sdate')->nullable();
            $table->date('arrive_edate')->nullable();            
            $table->boolean('reward_sales')->nullable();
            $table->boolean('add_on_beg')->nullable();
            $table->boolean('add_on_end')->nullable();
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
        Schema::dropIfExists('customer_service_report');
    }
};
