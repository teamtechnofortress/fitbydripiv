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
        Schema::create('patient_encounter', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();            
            $table->string('type')->nullable();            
            $table->string('ingredients')->nullable();            
            $table->string('name')->nullable();            
            $table->string('dosage')->nullable();            
            $table->integer('quantity')->nullable();            
            $table->boolean("paid")->default(false);
            $table->boolean("deleted")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_encounter');
    }
}; 
