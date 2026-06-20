<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('state_code', 10)->nullable()->after('product_id');
            $table->foreignId('dr_network_id')->nullable()->after('state_code')->constrained('dr_networks')->nullOnDelete();
            $table->foreignId('network_flow_id')->nullable()->after('dr_network_id')->constrained('network_flow_definitions')->nullOnDelete();
            $table->string('network_flow_key', 100)->nullable()->after('network_flow_id');
            $table->string('network_product_identifier')->nullable()->after('network_flow_key');

            $table->index(['dr_network_id', 'network_flow_id'], 'orders_dr_network_flow_index');
            $table->index('state_code', 'orders_state_code_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_dr_network_flow_index');
            $table->dropIndex('orders_state_code_index');
            $table->dropConstrainedForeignId('network_flow_id');
            $table->dropConstrainedForeignId('dr_network_id');
            $table->dropColumn([
                'state_code',
                'network_flow_key',
                'network_product_identifier',
            ]);
        });
    }
};
