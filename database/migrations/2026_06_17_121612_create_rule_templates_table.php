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
        Schema::create('rule_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique();
            $table->string('template_name');
            $table->text('description');
            $table->string('rule_type');
            $table->json('config_schema');
            $table->json('condition_schema')->nullable();
            $table->json('action_schema')->nullable();
            $table->json('example_config')->nullable();
            $table->json('example_conditions')->nullable();
            $table->json('example_actions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['rule_type', 'is_active'], 'rule_templates_type_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_templates');
    }
};
