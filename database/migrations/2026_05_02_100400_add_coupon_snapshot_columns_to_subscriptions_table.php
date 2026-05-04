<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignUuid('pricing_option_id')->nullable()->after('product_id')->constrained('pricing_options')->nullOnDelete();
            $table->foreignUuid('coupon_id')->nullable()->after('pricing_option_id')->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->decimal('base_recurring_amount', 10, 2)->nullable()->after('discount_percentage');
            $table->decimal('discounted_recurring_amount', 10, 2)->nullable()->after('base_recurring_amount');
            $table->string('discount_duration_type')->nullable()->after('discounted_recurring_amount');
            $table->integer('discount_remaining_cycles')->nullable()->after('discount_duration_type');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['pricing_option_id']);
            $table->dropForeign(['coupon_id']);
            $table->dropColumn([
                'pricing_option_id',
                'coupon_id',
                'coupon_code',
                'base_recurring_amount',
                'discounted_recurring_amount',
                'discount_duration_type',
                'discount_remaining_cycles',
            ]);
        });
    }
};
