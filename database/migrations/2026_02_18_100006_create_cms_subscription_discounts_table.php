<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_subscription_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->tinyInteger('frequency_months');
            $table->decimal('discount_percentage', 5, 2);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('cms_products')->onDelete('cascade');
            $table->unique(['product_id', 'frequency_months']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_subscription_discounts');
    }
};
