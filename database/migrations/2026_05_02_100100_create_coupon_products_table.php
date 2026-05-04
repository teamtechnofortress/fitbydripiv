<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['coupon_id', 'product_id'], 'uniq_coupon_products_coupon_product');
            $table->index('product_id', 'idx_coupon_products_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_products');
    }
};
