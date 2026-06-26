<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dr_network_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dr_network_id')->constrained('dr_networks')->cascadeOnDelete();
            $table->string('adapter_key', 100);
            $table->string('event_type', 150);
            $table->string('external_event_id')->nullable();
            $table->string('external_case_id')->nullable()->index();
            $table->string('external_order_id')->nullable()->index();
            $table->string('idempotency_hash', 64);
            $table->string('status', 50)->default('pending');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->longText('raw_body')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['dr_network_id', 'idempotency_hash'], 'dr_network_webhook_events_network_hash_unique');
            $table->index(['dr_network_id', 'status'], 'dr_network_webhook_events_network_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dr_network_webhook_events');
    }
};
