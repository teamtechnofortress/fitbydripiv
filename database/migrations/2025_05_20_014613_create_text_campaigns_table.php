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
        Schema::create('text_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('staff_id');
            $table->text('message');
            $table->text('company_signature');
            $table->boolean('include_signature')->default(false);
            $table->date('send_date');
            $table->time('send_time');
            $table->integer('texts_per_send');
            $table->integer('patient_start');
            $table->integer('patient_end');
            $table->boolean('sent')->default(false);
            $table->boolean("deleted")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('text_campaigns');
    }
};
