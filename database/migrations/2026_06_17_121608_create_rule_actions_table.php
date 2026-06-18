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
        Schema::create('rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configurable_rule_id')
                ->constrained('configurable_rules')
                ->cascadeOnDelete();
            $table->string('action_type');
            $table->json('action_payload');
            $table->text('description')->nullable();
            $table->integer('execution_order')->default(0);
            $table->boolean('stop_on_failure')->default(false);
            $table->integer('retry_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['configurable_rule_id', 'execution_order'], 'rule_actions_rule_execution_index');
            $table->index(['action_type'], 'rule_actions_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_actions');
    }
};
