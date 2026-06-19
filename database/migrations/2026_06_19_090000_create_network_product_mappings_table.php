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
            $table->string('identifier');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['dr_network_id', 'product_id'], 'network_product_unique');
            $table->index(['dr_network_id', 'is_active'], 'network_product_network_active_index');
            $table->index('product_id', 'network_product_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_product_mappings');
    }
};
