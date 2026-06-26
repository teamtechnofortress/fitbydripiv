<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('dr_network_id')->constrained('dr_networks');
            $table->string('network_case_id')->nullable()->index();
            $table->string('network_status')->nullable();
            $table->string('internal_status')->default('submitted');
            $table->json('network_metadata')->nullable();
            $table->decimal('payable_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['dr_network_id', 'internal_status'], 'consultation_records_network_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_records');
    }
};
