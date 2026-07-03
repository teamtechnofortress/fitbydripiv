<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dr_network_id')->constrained('dr_networks')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('flow_id')->constrained('network_flow_definitions')->cascadeOnDelete();
            $table->string('external_service_id');
            $table->string('external_service_key')->nullable();
            $table->json('external_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['dr_network_id', 'product_id', 'flow_id'], 'network_product_flow_unique');
            $table->index(['dr_network_id', 'is_active'], 'network_product_network_active_index');
            $table->index('product_id', 'network_product_product_index');
            $table->index('flow_id', 'network_product_flow_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_product_mappings');
    }
};
