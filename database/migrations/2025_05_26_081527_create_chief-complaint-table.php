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
        Schema::create('chief_complaint', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->integer('staff_id')->nullable();
            $table->date('date')->nullable();
            $table->string('treatment_type')->nullable();
            $table->text('goal')->nullable();
            $table->string('symptoms')->nullable();
            $table->string('BP')->nullable();            
            $table->string('HR')->nullable();            
            $table->string('Temp')->nullable();            
            $table->string('WT')->nullable();            
            $table->string('PE')->nullable();            
            $table->boolean("deleted")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chief_complaint');
    }
};
