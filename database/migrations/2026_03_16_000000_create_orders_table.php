<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->uuid('product_id');
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->integer('billing_cycle_number')->nullable();
            $table->enum('purchase_type', ['one_time', 'subscription']);
            $table->enum('pricing_type', ['base', 'micro_dose', 'sample']);
            $table->uuid('subscription_discount_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid');
            $table->string('stripe_checkout_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')
                ->references('id')
                ->on('patient')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('cms_products')
                ->cascadeOnDelete();

            $table->foreign('subscription_discount_id')
                ->references('id')
                ->on('cms_subscription_discounts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
