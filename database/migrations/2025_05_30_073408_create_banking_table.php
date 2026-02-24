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
        Schema::create('banking', function (Blueprint $table) {
            $table->id();
            $table->string('institution');
            $table->string('account');
            $table->string('routing');
            $table->string('sales_tax_rate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banking');
    }
};
