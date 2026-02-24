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
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->boolean("hours_worked")->nullable();
            $table->boolean("salary")->nullable();
            $table->boolean("calculated_overtime")->nullable();            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->dropColumn("hours_worked");            
            $table->dropColumn("salary");            
            $table->dropColumn("calculated_overtime");            
        });           
    }
};
