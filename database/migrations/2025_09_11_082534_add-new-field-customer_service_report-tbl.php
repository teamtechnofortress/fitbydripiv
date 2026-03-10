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
        Schema::table('customer_service_report', function (Blueprint $table) {
            $table->boolean("is_arrive_time")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_service_report', function (Blueprint $table) {
            $table->dropColumn("is_arrive_time");            
        });
    }
};
