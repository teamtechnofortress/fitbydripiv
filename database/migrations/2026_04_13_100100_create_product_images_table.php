<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('slot_position')->default(0);
            $table->string('image_url', 500);
            $table->enum('image_type', ['cover', 'portrait', 'landscape', 'gallery']);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('product_id');
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
