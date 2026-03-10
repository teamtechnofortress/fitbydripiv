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
        Schema::create('intake_2', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->boolean('constitutional')->nullable();
            $table->boolean('head')->nullable();
            $table->boolean('eyes')->nullable();
            $table->boolean('nose')->nullable();
            $table->boolean('mouth')->nullable();
            $table->boolean('ears')->nullable();
            $table->boolean('throat_neck')->nullable();
            $table->boolean('respiratory')->nullable();
            $table->boolean('cardiovascular')->nullable();
            $table->boolean('gastrointestinal')->nullable();
            $table->boolean('musculoskeletal')->nullable();   
            $table->boolean('skin')->nullable();           
            $table->boolean('endocrine')->nullable();           
            $table->boolean('urinary')->nullable();           
            $table->boolean('male_genitalia')->nullable();
            $table->boolean('neurological')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intake_2');
    }
};
