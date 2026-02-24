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
        Schema::create('patient', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->date('birthday')->nullable();
            $table->string('cell')->nullable();
            $table->string('phone')->nullable();
            $table->string('home')->nullable();
            $table->string('emergency')->nullable();
            $table->string('contact')->nullable();
            $table->string('referred')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('age')->nullable();
            $table->string('gender')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('current_conditions')->nullable();
            $table->string('current_allergies')->nullable();
            $table->string('allergy_reactions')->nullable();
            $table->string('current_medications')->nullable();
            $table->boolean('pregnant')->nullable();
            $table->boolean('tobacco')->nullable();
            $table->boolean('drug_use')->nullable();
            $table->enum('alcohol', ['no', 'daily', 'weekly'])->default('no');
            $table->string('signature')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient');
    }
};
