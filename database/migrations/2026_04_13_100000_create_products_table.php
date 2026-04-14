<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->enum('category', [
                'weight_loss',
                'wellness',
                'longevity',
                'vitamin_therapy',
                'other',
            ]);
            $table->text('description')->nullable();
            $table->text('about_treatment')->nullable();
            $table->text('how_it_works')->nullable();
            $table->text('key_ingredients')->nullable();
            $table->text('treatment_duration')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('research_description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->string('completion_status', 20)->default('draft');
            $table->unsignedInteger('completion_percentage')->default(0);
            $table->unsignedInteger('completion_step')->default(1);
            $table->uuid('cover_image_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('completion_status');
            $table->index('category');
            $table->index('is_featured');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
