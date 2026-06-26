<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('dr_network_config_values', 'lookup_hash')) {
            return;
        }

        Schema::table('dr_network_config_values', function (Blueprint $table): void {
            $table->string('lookup_hash', 64)->nullable()->after('value');
            $table->index(['key', 'lookup_hash'], 'dr_network_config_values_key_lookup_hash_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('dr_network_config_values', 'lookup_hash')) {
            return;
        }

        Schema::table('dr_network_config_values', function (Blueprint $table): void {
            $table->dropIndex('dr_network_config_values_key_lookup_hash_index');
            $table->dropColumn('lookup_hash');
        });
    }
};
