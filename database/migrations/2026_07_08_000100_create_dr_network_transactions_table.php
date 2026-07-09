<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dr_network_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dr_network_id')->constrained('dr_networks');
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('consultation_record_id')->nullable()->constrained('consultation_records')->nullOnDelete();
            $table->uuid('product_id')->nullable();
            $table->foreignId('flow_id')->nullable()->constrained('network_flow_definitions')->nullOnDelete();
            $table->decimal('patient_paid_amount', 10, 2);
            $table->decimal('network_owed_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active');
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->unique('order_id');
            $table->index(['dr_network_id', 'status']);
            $table->index(['dr_network_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dr_network_transactions');
    }
};
