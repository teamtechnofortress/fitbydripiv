<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_intake_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('network_intake_questions')->cascadeOnDelete();
            $table->longText('answer_value')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'question_id'], 'order_intake_answers_order_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_intake_answers');
    }
};
