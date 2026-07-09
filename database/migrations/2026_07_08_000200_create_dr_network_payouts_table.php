<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dr_network_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dr_network_id')->constrained('dr_networks');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('method');
            $table->string('reference_number')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('initiated_by')->constrained('users');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['dr_network_id', 'status']);
            $table->index(['dr_network_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dr_network_payouts');
    }
};
