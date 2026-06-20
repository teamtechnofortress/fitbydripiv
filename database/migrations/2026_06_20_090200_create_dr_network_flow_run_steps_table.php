<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dr_network_flow_run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_run_id')->constrained('dr_network_flow_runs')->cascadeOnDelete();
            $table->string('step_key');
            $table->string('status');
            $table->json('output')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['flow_run_id', 'step_key'], 'dr_network_flow_run_steps_run_step_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dr_network_flow_run_steps');
    }
};
