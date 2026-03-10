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
        Schema::table('medrx_reports', function (Blueprint $table) {
            $table->boolean("iv_solutions")->nullable();
            $table->boolean("injectable")->nullable();
            $table->boolean("peptides")->nullable();            
            $table->boolean("consumables")->nullable();            
            $table->boolean("on_hand")->nullable();            
            $table->boolean("sold_invoiced")->nullable();            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medrx_reports', function (Blueprint $table) {
            $table->dropColumn("iv_solutions");            
            $table->dropColumn("injectable");            
            $table->dropColumn("peptides");            
            $table->dropColumn("consumables");            
            $table->dropColumn("on_hand");            
            $table->dropColumn("sold_invoiced");            
        });           
    }
};
