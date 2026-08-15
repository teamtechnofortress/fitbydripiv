<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('product_final_amount', 10, 2)->nullable()->after('pricing_option_id');
        });

        DB::table('orders')
            ->select([
                'id',
                'price',
                'coupon_discount_amount',
                'final_amount',
                'dr_network_patient_fee_amount',
            ])
            ->whereNull('product_final_amount')
            ->orderBy('id')
            ->chunkById(500, function ($orders): void {
                foreach ($orders as $order) {
                    $finalAmount = $order->final_amount ?? $order->price ?? 0;
                    $patientFee = $order->dr_network_patient_fee_amount ?? 0;
                    $couponDiscount = $order->coupon_discount_amount ?? 0;
                    $productFinalAmount = max(0, round((float) $finalAmount - (float) $patientFee + (float) $couponDiscount, 2));

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['product_final_amount' => $productFinalAmount]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('product_final_amount');
        });
    }
};
