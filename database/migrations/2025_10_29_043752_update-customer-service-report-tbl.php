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
        //
        Schema::table('customer_service_report', function (Blueprint $table) {
            $table->time('arrive_stime')->nullable();
            $table->time('arrive_etime')->nullable();
            $table->dropColumn('arrive_sdate');
            $table->dropColumn('arrive_edate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('customer_service_report', function (Blueprint $table) {
            $table->date('arrive_sdate')->nullable();
            $table->date('arrive_edate')->nullable();
            $table->dropColumn('arrive_stime');
            $table->dropColumn('arrive_etime');
        });
    }
};
