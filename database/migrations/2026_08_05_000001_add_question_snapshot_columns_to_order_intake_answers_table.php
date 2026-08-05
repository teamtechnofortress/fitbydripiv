<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_intake_answers', function (Blueprint $table): void {
            $table->string('question_key', 100)->nullable()->after('question_id');
            $table->string('question_text')->nullable()->after('question_key');
            $table->string('input_type', 50)->nullable()->after('question_text');
            $table->string('network_field_mapping', 150)->nullable()->after('input_type');

            $table->index(['order_id', 'question_key'], 'order_intake_answers_order_question_key_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_intake_answers', function (Blueprint $table): void {
            $table->dropIndex('order_intake_answers_order_question_key_index');
            $table->dropColumn([
                'question_key',
                'question_text',
                'input_type',
                'network_field_mapping',
            ]);
        });
    }
};
