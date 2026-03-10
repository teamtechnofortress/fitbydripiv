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
        Schema::create('product_metrics_report', function (Blueprint $table) {
            $table->id();
            $table->string('frequency')->nullable();            
            $table->date('range_sdate')->nullable();
            $table->date('range_edate')->nullable();
            
            $table->boolean('isSemaglutide')->nullable();
            $table->boolean('isTirzepatide')->nullable();

            $table->boolean('isIV')->nullable();
            $table->boolean('isInjections')->nullable();
            $table->boolean('isPeptides')->nullable();
            $table->boolean('isOther')->nullable();

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
        Schema::dropIfExists('product_metrics_report');
    }
};
