<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dr_network_flow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('dr_network_id')->constrained('dr_networks');
            $table->foreignId('flow_id')->constrained('network_flow_definitions');
            $table->string('status');
            $table->string('current_step_key')->nullable();
            $table->json('context')->nullable();
            $table->string('pause_reason')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index('status', 'dr_network_flow_runs_status_index');
            $table->index(['dr_network_id', 'status'], 'dr_network_flow_runs_network_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dr_network_flow_runs');
    }
};
