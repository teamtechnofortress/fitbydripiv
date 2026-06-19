<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_intake_question_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dr_network_id')->constrained('dr_networks')->cascadeOnDelete();
            $table->foreignId('flow_id')
                ->nullable()
                ->constrained('network_flow_definitions')
                ->restrictOnDelete();
            $table->unsignedBigInteger('flow_scope_id')->storedAs('coalesce(`flow_id`, 0)');
            $table->string('product_code', 100)->default('*');
            $table->string('state_code', 10)->default('*');
            $table->string('set_key', 150);
            $table->string('set_name', 150);
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'dr_network_id',
                    'flow_scope_id',
                    'product_code',
                    'state_code',
                    'version',
                ],
                'network_intake_set_unique'
            );
            $table->unique(['set_key', 'version'], 'network_intake_set_key_version_unique');
            $table->index(
                [
                    'dr_network_id',
                    'flow_id',
                    'product_code',
                    'state_code',
                    'status',
                ],
                'network_intake_set_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_intake_question_sets');
    }
};
