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
        Schema::create('reward_report', function (Blueprint $table) {
            $table->id();
            $table->string('frequency')->nullable();            
            $table->date('range_sdate')->nullable();
            $table->date('range_edate')->nullable();
            
            $table->boolean('totalRewardPurchases')->nullable();
            $table->boolean('rewardGold')->nullable();
            $table->boolean('rewardSilver')->nullable();
            $table->boolean('rewardBronze')->nullable();
            $table->boolean('rewardDiscount')->nullable();

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
        Schema::dropIfExists('reward_report');
    }
};
