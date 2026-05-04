<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignUuid('coupon_id')->nullable()->after('pricing_option_id')->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->decimal('base_amount', 10, 2)->default(0)->after('price');
            $table->decimal('coupon_discount_amount', 10, 2)->default(0)->after('base_amount');
            $table->decimal('final_amount', 10, 2)->default(0)->after('coupon_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn([
                'coupon_id',
                'coupon_code',
                'base_amount',
                'coupon_discount_amount',
                'final_amount',
            ]);
        });
    }
};
