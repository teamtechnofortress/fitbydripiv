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
        Schema::table('intake_1', function (Blueprint $table) {
            // $table->boolean('dehydration')->change();
            DB::statement('ALTER TABLE intake_1 CHANGE dehydration pain BOOLEAN;');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intake_1', function (Blueprint $table) { 
            // $table->renameColumn('pain', 'dehydration');
            DB::statement('ALTER TABLE intake_1 CHANGE pain dehydration BOOLEAN;');
        });
    }
};
