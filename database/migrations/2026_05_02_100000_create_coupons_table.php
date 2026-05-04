<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['percent', 'fixed']);
            $table->decimal('value', 10, 2);
            $table->enum('scope', ['global', 'product_specific'])->default('global');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->enum('applies_to', ['all', 'one_time', 'subscription'])->default('all');
            $table->boolean('first_order_only')->default(false);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'expires_at'], 'idx_coupons_active_window');
            $table->index(['scope', 'applies_to'], 'idx_coupons_scope_applies_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
