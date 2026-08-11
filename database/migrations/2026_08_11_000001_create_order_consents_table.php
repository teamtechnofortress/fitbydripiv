<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->foreignId('consultation_record_id')->nullable()->constrained('consultation_records')->nullOnDelete();
            $table->string('consent_key', 100);
            $table->string('consent_title')->nullable();
            $table->string('content_version', 100);
            $table->string('content_hash', 128);
            $table->boolean('accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')
                ->references('id')
                ->on('patient')
                ->nullOnDelete();

            $table->unique(['order_id', 'consent_key', 'content_version'], 'order_consents_order_key_version_unique');
            $table->index(['order_id', 'consent_key', 'accepted'], 'order_consents_order_key_accepted_index');
            $table->index(['patient_id', 'consent_key'], 'order_consents_patient_key_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_consents');
    }
};
