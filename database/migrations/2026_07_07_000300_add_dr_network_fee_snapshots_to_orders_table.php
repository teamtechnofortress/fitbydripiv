<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'dr_network_fee_amount')) {
                $table->decimal('dr_network_fee_amount', 10, 2)->default(0)->after('network_product_identifier');
            }

            if (! Schema::hasColumn('orders', 'dr_network_patient_fee_amount')) {
                $table->decimal('dr_network_patient_fee_amount', 10, 2)->default(0)->after('dr_network_fee_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'dr_network_patient_fee_amount')) {
                $table->dropColumn('dr_network_patient_fee_amount');
            }

            if (Schema::hasColumn('orders', 'dr_network_fee_amount')) {
                $table->dropColumn('dr_network_fee_amount');
            }
        });
    }
};
