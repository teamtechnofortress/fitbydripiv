<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('network_product_mappings')) {
            return;
        }

        $hasLegacyIdentifier = Schema::hasColumn('network_product_mappings', 'identifier');

        Schema::table('network_product_mappings', function (Blueprint $table): void {
            if (! Schema::hasColumn('network_product_mappings', 'flow_id')) {
                $table->foreignId('flow_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('network_flow_definitions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('network_product_mappings', 'external_service_id')) {
                $table->string('external_service_id')->nullable()->after('flow_id');
            }

            if (! Schema::hasColumn('network_product_mappings', 'external_service_key')) {
                $table->string('external_service_key')->nullable()->after('external_service_id');
            }

            if (! Schema::hasColumn('network_product_mappings', 'external_config')) {
                $table->json('external_config')->nullable()->after('external_service_key');
            }
        });

        if ($hasLegacyIdentifier) {
            DB::table('network_product_mappings')
                ->whereNull('external_service_key')
                ->update([
                    'external_service_key' => DB::raw('identifier'),
                    'external_service_id' => DB::raw('identifier'),
                ]);
        }

        if ($hasLegacyIdentifier) {
            Schema::table('network_product_mappings', function (Blueprint $table): void {
                $table->dropUnique('network_product_unique');
                $table->unique(['dr_network_id', 'product_id', 'flow_id'], 'network_product_flow_unique');
                $table->index('flow_id', 'network_product_flow_index');
            });

            Schema::table('network_product_mappings', function (Blueprint $table): void {
                $table->dropColumn('identifier');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('network_product_mappings')) {
            return;
        }

        Schema::table('network_product_mappings', function (Blueprint $table): void {
            if (! Schema::hasColumn('network_product_mappings', 'identifier')) {
                $table->string('identifier')->nullable()->after('product_id');
            }
        });

        DB::table('network_product_mappings')
            ->whereNull('identifier')
            ->update(['identifier' => DB::raw('COALESCE(external_service_key, external_service_id)')]);
    }
};
