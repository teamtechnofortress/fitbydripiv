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
        Schema::create('intake_1', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->boolean('goal_iv')->nullable();
            $table->boolean('goal_injection')->nullable();
            $table->boolean('goal_other')->nullable();
            $table->boolean('hydration')->nullable();
            $table->boolean('energy')->nullable();
            $table->boolean('recovery')->nullable();
            $table->boolean('dehydration')->nullable();
            $table->boolean('supplements')->nullable();
            $table->boolean('fatigue')->nullable();
            $table->boolean('headache')->nullable();
            $table->boolean('soreness')->nullable();
            $table->boolean('current_illness')->nullable();
            $table->boolean('recent_illness')->nullable();
            $table->boolean('hangover')->nullable();
            $table->boolean('low_energy')->nullable();
            $table->boolean('immunity')->nullable();            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intake_1');
    }
};
