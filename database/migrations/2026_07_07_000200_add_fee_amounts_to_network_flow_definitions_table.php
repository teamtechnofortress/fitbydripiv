<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('network_flow_definitions', function (Blueprint $table): void {
            if (! Schema::hasColumn('network_flow_definitions', 'network_fee_amount')) {
                $table->decimal('network_fee_amount', 10, 2)->default(0)->after('steps');
            }

            if (! Schema::hasColumn('network_flow_definitions', 'patient_fee_amount')) {
                $table->decimal('patient_fee_amount', 10, 2)->default(0)->after('network_fee_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('network_flow_definitions', function (Blueprint $table): void {
            if (Schema::hasColumn('network_flow_definitions', 'patient_fee_amount')) {
                $table->dropColumn('patient_fee_amount');
            }

            if (Schema::hasColumn('network_flow_definitions', 'network_fee_amount')) {
                $table->dropColumn('network_fee_amount');
            }
        });
    }
};
