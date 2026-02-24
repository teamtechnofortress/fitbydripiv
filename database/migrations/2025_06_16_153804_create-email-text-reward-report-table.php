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
        Schema::create('email_text_reward_report', function (Blueprint $table) {
            $table->id();
            $table->string('frequency')->nullable();            
            $table->date('range_sdate')->nullable();
            $table->date('range_edate')->nullable();

            $table->boolean('email_sent')->nullable();
            $table->boolean('text_sent')->nullable();
            $table->boolean('reward_sent')->nullable();
            $table->boolean('birth_sent')->nullable();

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
        Schema::dropIfExists('email_text_reward_report');
    }
};
