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
        Schema::table('staff_reports', function (Blueprint $table) {
            $table->boolean("late_checkin")->nullable();
            $table->boolean("early_checkin")->nullable();
            $table->boolean("overtime_incident")->nullable();
            $table->boolean("late_schedule")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_reports', function (Blueprint $table) {
            $table->dropColumn("late_checkin");            
            $table->dropColumn("early_checkin");            
            $table->dropColumn("overtime_incident");            
            $table->dropColumn("late_schedule");            
        });
    }
};
