<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_state_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('dr_network_id')->constrained('dr_networks')->cascadeOnDelete();
            $table->foreignId('flow_id')->constrained('network_flow_definitions')->restrictOnDelete();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->json('rules')->nullable();
            $table->timestamps();

            $table->unique(
                ['state_id', 'dr_network_id', 'flow_id'],
                'network_state_flow_unique'
            );
            $table->index(['state_id', 'is_active', 'priority'], 'network_state_mappings_state_active_priority_index');
            $table->index(['dr_network_id', 'is_active'], 'network_state_mappings_network_active_index');
            $table->index(['flow_id', 'is_active'], 'network_state_mappings_flow_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_state_mappings');
    }
};
