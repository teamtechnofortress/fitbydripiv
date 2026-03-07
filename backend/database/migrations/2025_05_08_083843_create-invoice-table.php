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
        Schema::create('invoice', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();            
            $table->text('data')->nullable();   
            $table->double('tip')->nullable();
            $table->double('tax')->nullable();
            $table->double('totalPrice')->nullable();
            $table->boolean("isEmailing")->default(false);
            $table->boolean("isPaid")->default(false);
            $table->boolean("deleted")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
