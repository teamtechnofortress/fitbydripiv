<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pricing_id')->constrained('product_pricing')->cascadeOnDelete();
            $table->enum('billing_interval', ['one_time', 'day', 'week', 'month', 'year']);
            $table->integer('interval_count')->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('final_price', 10, 2);
            $table->string('label', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('pricing_id', 'idx_pricing_options_pricing_id');
            $table->index('is_default', 'idx_pricing_options_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_options');
    }
};
