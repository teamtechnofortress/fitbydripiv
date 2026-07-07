<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('network_flow_definitions')) {
            return;
        }

        if (! Schema::hasColumn('network_flow_definitions', 'dr_network_id')) {
            Schema::table('network_flow_definitions', function (Blueprint $table): void {
                $table->foreignId('dr_network_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('dr_networks')
                    ->cascadeOnDelete();
            });
        }

        $this->assertSingleNetworkPerExistingFlow('network_state_mappings');
        $this->assertSingleNetworkPerExistingFlow('network_product_mappings');

        $this->backfillFlowOwnersFrom('network_state_mappings');
        $this->backfillFlowOwnersFrom('network_product_mappings');
        $this->backfillOlaHealthFlowOwners();

        $this->assertAllFlowsHaveOwner();
        $this->assertFlowReferencesBelongToOwner('network_state_mappings');
        $this->assertFlowReferencesBelongToOwner('network_product_mappings');

        DB::statement('ALTER TABLE network_flow_definitions MODIFY dr_network_id BIGINT UNSIGNED NOT NULL');

        Schema::table('network_flow_definitions', function (Blueprint $table): void {
            $table->dropUnique('network_flow_definitions_flow_key_unique');
            $table->unique(['dr_network_id', 'flow_key'], 'network_flow_definitions_network_flow_key_unique');
            $table->unique(['id', 'dr_network_id'], 'network_flow_definitions_id_network_unique');
            $table->index(['dr_network_id', 'is_active'], 'network_flow_definitions_network_active_index');
        });

        if (Schema::hasTable('network_state_mappings')) {
            Schema::table('network_state_mappings', function (Blueprint $table): void {
                $table->dropForeign(['flow_id']);
                $table->index(['flow_id', 'dr_network_id'], 'network_state_mappings_flow_network_index');
                $table->foreign(['flow_id', 'dr_network_id'], 'network_state_mappings_flow_network_foreign')
                    ->references(['id', 'dr_network_id'])
                    ->on('network_flow_definitions')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('network_product_mappings')) {
            Schema::table('network_product_mappings', function (Blueprint $table): void {
                $table->dropForeign(['flow_id']);
                $table->index(['flow_id', 'dr_network_id'], 'network_product_mappings_flow_network_index');
                $table->foreign(['flow_id', 'dr_network_id'], 'network_product_mappings_flow_network_foreign')
                    ->references(['id', 'dr_network_id'])
                    ->on('network_flow_definitions')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('network_flow_definitions')) {
            return;
        }

        if (Schema::hasTable('network_product_mappings')) {
            Schema::table('network_product_mappings', function (Blueprint $table): void {
                $table->dropForeign('network_product_mappings_flow_network_foreign');
                $table->dropIndex('network_product_mappings_flow_network_index');
                $table->foreign('flow_id')
                    ->references('id')
                    ->on('network_flow_definitions')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('network_state_mappings')) {
            Schema::table('network_state_mappings', function (Blueprint $table): void {
                $table->dropForeign('network_state_mappings_flow_network_foreign');
                $table->dropIndex('network_state_mappings_flow_network_index');
                $table->foreign('flow_id')
                    ->references('id')
                    ->on('network_flow_definitions')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('network_flow_definitions', 'dr_network_id')) {
            Schema::table('network_flow_definitions', function (Blueprint $table): void {
                $table->dropUnique('network_flow_definitions_network_flow_key_unique');
                $table->dropUnique('network_flow_definitions_id_network_unique');
                $table->dropIndex('network_flow_definitions_network_active_index');
                $table->unique('flow_key');
                $table->dropConstrainedForeignId('dr_network_id');
            });
        }
    }

    private function assertSingleNetworkPerExistingFlow(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $conflictingFlowIds = DB::table($table)
            ->select('flow_id')
            ->whereNotNull('flow_id')
            ->groupBy('flow_id')
            ->havingRaw('COUNT(DISTINCT dr_network_id) > 1')
            ->pluck('flow_id')
            ->all();

        if ($conflictingFlowIds !== []) {
            throw new RuntimeException(sprintf(
                'Cannot make network_flow_definitions network-owned because [%s] references shared flow ids across multiple networks: %s. Split those flow definitions first.',
                $table,
                implode(', ', $conflictingFlowIds)
            ));
        }
    }

    private function backfillFlowOwnersFrom(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::statement("
            UPDATE network_flow_definitions AS flow
            JOIN (
                SELECT flow_id, MIN(dr_network_id) AS dr_network_id
                FROM {$table}
                WHERE flow_id IS NOT NULL
                GROUP BY flow_id
            ) AS owner ON owner.flow_id = flow.id
            SET flow.dr_network_id = owner.dr_network_id,
                flow.updated_at = CURRENT_TIMESTAMP
            WHERE flow.dr_network_id IS NULL
        ");
    }

    private function backfillOlaHealthFlowOwners(): void
    {
        if (! Schema::hasTable('dr_networks')) {
            return;
        }

        $olaHealthNetworkId = DB::table('dr_networks')
            ->where('slug', 'ola-health')
            ->value('id');

        if (! $olaHealthNetworkId) {
            return;
        }

        DB::table('network_flow_definitions')
            ->whereNull('dr_network_id')
            ->whereIn('flow_key', [
                'ola_health_async_review',
                'ola_health_video_consultation',
                // 'ola_health_follow_up_async_review',
                'async_review',
                'video_consultation',
                // 'follow_up_async_review',
            ])
            ->update([
                'dr_network_id' => $olaHealthNetworkId,
                'updated_at' => now(),
            ]);
    }

    private function assertAllFlowsHaveOwner(): void
    {
        $unassignedFlowKeys = DB::table('network_flow_definitions')
            ->whereNull('dr_network_id')
            ->pluck('flow_key')
            ->all();

        if ($unassignedFlowKeys !== []) {
            throw new RuntimeException(sprintf(
                'Cannot make network_flow_definitions.dr_network_id required because these flows have no network owner: %s.',
                implode(', ', $unassignedFlowKeys)
            ));
        }
    }

    private function assertFlowReferencesBelongToOwner(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $invalidIds = DB::table("{$table} AS mapped")
            ->join('network_flow_definitions AS flow', 'flow.id', '=', 'mapped.flow_id')
            ->whereColumn('mapped.dr_network_id', '!=', 'flow.dr_network_id')
            ->limit(20)
            ->pluck('mapped.id')
            ->all();

        if ($invalidIds !== []) {
            throw new RuntimeException(sprintf(
                'Cannot add composite flow ownership foreign key because [%s] has rows pointing to flows owned by another network. Invalid ids: %s.',
                $table,
                implode(', ', $invalidIds)
            ));
        }
    }
};
