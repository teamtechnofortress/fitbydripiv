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
        Schema::create('patient_assessment', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->integer('staff_id')->nullable();
            $table->date('date')->nullable();            
            $table->text('diag_description')->nullable();
            $table->text('diag_problem')->nullable();
            $table->text('diag_comment')->nullable();
            $table->text('notes')->nullable();
            $table->boolean("deleted")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_assessment');
    }
};
