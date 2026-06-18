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
        Schema::create('rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configurable_rule_id')
                ->constrained('configurable_rules')
                ->cascadeOnDelete();
            $table->string('condition_type');
            $table->string('operator');
            $table->json('values');
            $table->integer('priority')->default(0);
            $table->enum('logic_operator', ['AND', 'OR'])->default('AND');
            $table->boolean('negate')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['configurable_rule_id', 'priority'], 'rule_conditions_rule_priority_index');
            $table->index(['condition_type'], 'rule_conditions_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_conditions');
    }
};
