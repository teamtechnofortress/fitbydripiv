<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->text('full_description')->nullable();
            $table->json('benefits')->nullable();
            $table->json('treatment_details')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('portrait_image')->nullable();
            $table->string('landscape_image')->nullable();
            $table->json('image_gallery')->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('micro_dose_price', 10, 2)->nullable();
            $table->decimal('sample_price', 10, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->integer('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('cms_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_products');
    }
};
