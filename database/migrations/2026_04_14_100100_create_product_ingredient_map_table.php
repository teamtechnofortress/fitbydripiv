<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ingredient_map', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['product_id', 'ingredient_id']);
            $table->index(['product_id', 'sort_order']);
            $table->index('ingredient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredient_map');
    }
};
