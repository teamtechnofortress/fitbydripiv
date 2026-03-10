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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();;
            $table->integer('staff_id')->nullable();;
            $table->text('content')->nullable();;
            $table->text('company_signature')->nullable();;
            $table->boolean('include_signature')->default(true);
            $table->date('send_date');
            $table->time('send_time');
            $table->integer('texts_per_send');
            $table->integer('patient_start');
            $table->integer('patient_end');
            $table->boolean('sent')->default(false);
            $table->boolean('archive_after_send')->default(false);
            $table->boolean("deleted")->default(false);
            $table->text('attachments')->nullable();;
            $table->text('contact')->nullable();;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
