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
        Schema::table('network_state_mappings', function (Blueprint $table) {
            $table->dropColumn('rules');
            $table->index(['state_id', 'is_active'], 'nsm_state_active_index');
            $table->index(['dr_network_id', 'is_active'], 'nsm_network_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('network_state_mappings', function (Blueprint $table) {
            $table->dropIndex('nsm_state_active_index');
            $table->dropIndex('nsm_network_active_index');
            $table->json('rules')->nullable()->after('is_active');
        });
    }
};
