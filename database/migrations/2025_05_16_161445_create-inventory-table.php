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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->double('size_mg')->nullable();
            $table->double('size_ml')->nullable();
            $table->integer('level_min')->nullable();
            $table->integer('level_max')->nullable();
            $table->double('dosage_min')->nullable();
            $table->double('dosage_max')->nullable();
            $table->string('ingredients')->nullable();
            $table->integer('sales_daily')->nullable();
            $table->integer('sales_monthly')->nullable();
            $table->text('dosage')->nullable();
            $table->integer('total_count')->nullable();
            $table->text('others')->nullable();
            $table->boolean("deleted")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
