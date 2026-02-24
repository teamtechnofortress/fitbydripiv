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
        Schema::table('chief_complaint_notes', function (Blueprint $table) {
            $table->text("allergies")->nullable();
            $table->text("medication")->nullable();
            $table->text("physical_exam")->nullable();
            $table->text("evaluation")->nullable();
            $table->text("vital_sign")->nullable();
            $table->text("assessment")->nullable();
            $table->text("plan_order")->nullable();
            $table->text("plan_care")->nullable();
            $table->text("plan_instruction")->nullable();
            $table->text("risk")->nullable();
            $table->text("benefit")->nullable();
            $table->text("reward")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chief_complaint_notes', function (Blueprint $table) {
            $table->dropColumn("allergies");
            $table->dropColumn("medication");
            $table->dropColumn("physical_exam");
            $table->dropColumn("evaluation");
            $table->dropColumn("vital_sign");
            $table->dropColumn("assessment");
            $table->dropColumn("plan_order");
            $table->dropColumn("plan_care");
            $table->dropColumn("plan_instruction");
            $table->dropColumn("risk");
            $table->dropColumn("benefit");
            $table->dropColumn("reward");
        });
    }
};
