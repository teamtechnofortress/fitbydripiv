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
        Schema::create('staff_payroll', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id')->nullable();
            $table->string('frequency')->nullable();
            $table->double('withholding')->nullable();
            $table->string('payrate')->nullable();
            $table->text('others')->nullable();
            $table->boolean("deleted")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_payroll');
    }
};
