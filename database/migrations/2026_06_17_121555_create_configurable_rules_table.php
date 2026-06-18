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
        Schema::create('configurable_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ruleable_type', 100)->nullable();
            $table->string('ruleable_id', 64)->nullable();
            $table->string('rule_type', 100);
            $table->string('rule_key')->unique();
            $table->string('rule_name');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->integer('execution_order')->default(0);
            $table->json('config');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->string('version')->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(
                ['ruleable_type', 'ruleable_id', 'rule_type', 'is_active'],
                'configurable_rules_ruleable_type_id_type_active_index'
            );
            $table->index(['rule_type', 'is_active'], 'configurable_rules_type_active_index');
            $table->index(['status', 'is_active'], 'configurable_rules_status_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurable_rules');
    }
};
