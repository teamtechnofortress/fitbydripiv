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
        Schema::create('chart_history', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();            
            $table->string('frequency')->nullable();            
            $table->boolean('isEncounters')->nullable();
            $table->boolean('isProducts')->nullable();            
            $table->date('range_sdate')->nullable();
            $table->date('range_edate')->nullable();
            $table->boolean('hasNotes')->nullable();
            $table->string('email')->nullable();
            $table->boolean("deleted")->default(false);
            $table->datetime("reported_date")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_history');
    }
};
