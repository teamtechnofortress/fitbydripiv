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
        Schema::create('special_promos', function (Blueprint $table) {
            $table->id();
            $table->string('promoTitle')->nullable();
            $table->integer('discountJoin')->nullable();
            $table->integer('discountForSilver')->nullable();
            $table->integer('volumeToSilver')->nullable();
            $table->integer('discountForBronze')->nullable();
            $table->integer('volumeToBronze')->nullable();
            $table->integer('discountForGold')->nullable();
            $table->integer('volumeToGold')->nullable();
            $table->boolean("deleted")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_promos');
    }
};
