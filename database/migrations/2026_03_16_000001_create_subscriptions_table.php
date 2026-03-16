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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('patient_id');
            $table->uuid('product_id');
            $table->integer('current_cycle_number')->default(1);
            $table->integer('total_cycles')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->integer('billing_frequency_months');
            $table->decimal('discount_percentage', 5, 2);
            $table->date('start_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'cancelled', 'paused', 'completed'])->default('active');
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('patient_id')
                ->references('id')
                ->on('patient')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('cms_products')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
