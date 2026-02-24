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
        //
        Schema::table('appointment', function (Blueprint $table) {
            // $table->renameColumn('title', 'patient_name');
            DB::statement('ALTER TABLE appointment CHANGE title patient_name VARCHAR(255);');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('appointment', function (Blueprint $table) {
            // $table->renameColumn('patient_name', 'title');
            DB::statement('ALTER TABLE appointment CHANGE patient_name title VARCHAR(255);');
        });
    }
};
