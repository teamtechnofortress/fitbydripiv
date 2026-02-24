<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patient_physical_exam', function (Blueprint $table) {           
            $table->string("BP")->nullable();
            $table->string("HR")->nullable();
            $table->string("Temp")->nullable();
            $table->string("WT")->nullable();
        });
        Schema::table('chief_complaint', function (Blueprint $table) {            
            $table->dropColumn("BP");
            $table->dropColumn("HR");
            $table->dropColumn("Temp");
            $table->dropColumn("WT");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_physical_exam', function (Blueprint $table) {            
            $table->dropColumn("BP");
            $table->dropColumn("HR");
            $table->dropColumn("Temp");
            $table->dropColumn("WT");
        });
        Schema::table('chief_complaint', function (Blueprint $table) {           
            $table->string("BP")->nullable();
            $table->string("HR")->nullable();
            $table->string("Temp")->nullable();
            $table->string("WT")->nullable();
        });
    }
};
