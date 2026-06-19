<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_intake_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_set_id')
                ->constrained('network_intake_question_sets')
                ->cascadeOnDelete();
            $table->string('question_key', 100);
            $table->string('question_text');
            $table->text('help_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('input_type', 50);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->boolean('is_conditional')->default(false);
            $table->json('condition_rules')->nullable();
            $table->string('network_field_mapping', 150)->nullable();
            $table->json('network_validation')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                [
                    'question_set_id',
                    'question_key',
                ],
                'network_intake_question_unique'
            );
            $table->index(
                [
                    'question_set_id',
                    'sort_order',
                    'is_active',
                ],
                'network_intake_question_order_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_intake_questions');
    }
};
