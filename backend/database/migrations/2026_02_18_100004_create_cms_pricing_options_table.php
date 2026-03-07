<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pricing_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('plan_name');
            $table->decimal('price', 10, 2);
            $table->string('billing_cycle')->nullable();
            $table->string('supply_duration')->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('cms_products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_pricing_options');
    }
};
