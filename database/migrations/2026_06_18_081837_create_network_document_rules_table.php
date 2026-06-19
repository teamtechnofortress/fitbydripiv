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
        Schema::create('network_document_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dr_network_id')->constrained('dr_networks')->cascadeOnDelete();
            $table->string('flow_key', 100);
            $table->string('state_code', 10)->nullable();
            $table->string('product_code', 100)->nullable();
            $table->string('rule_key')->unique();
            $table->string('rule_name');
            $table->integer('priority')->default(0);
            $table->string('requirement_type', 50);
            $table->enum('operator', ['any', 'all', 'exact'])->default('any');
            $table->json('document_ids');
            $table->boolean('is_required')->default(true);
            $table->json('conditions');
            $table->string('error_message')->nullable();
            $table->string('help_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                [
                    'dr_network_id',
                    'flow_key',
                    'state_code',
                    'product_code',
                    'requirement_type',
                ],
                'network_document_rule_unique'
            );
            $table->index(['dr_network_id', 'flow_key', 'state_code'], 'ndr_network_flow_state_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_document_rules');
    }
};
