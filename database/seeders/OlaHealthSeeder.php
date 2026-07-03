<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OlaHealthSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetOlaHealthData();

        $this->call([
            OlaHealthNetworkSeeder::class,
            OlaHealthDocumentRulesSeeder::class,
            OlaHealthIntakeQuestionsSeeder::class,
            OlaHealthProductMappingSeeder::class,
        ]);
    }

    private function resetOlaHealthData(): void
    {
        DB::table('orders')->update([
            'dr_network_id' => null,
            'network_flow_id' => null,
            'network_flow_key' => null,
            'network_product_identifier' => null,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->drNetworkTables() as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function drNetworkTables(): array
    {
        return [
            // Runtime data
            'dr_network_flow_run_steps',
            'dr_network_flow_runs',
            'dr_network_webhook_events',
            'consultation_records',
            'order_intake_answers',
            'order_documents',

            // Network configuration
            'network_intake_questions',
            'network_intake_question_sets',
            'network_document_rules',
            'network_product_mappings',
            'network_state_mappings',
            'dr_network_config_values',
            'dr_networks',
            'network_flow_definitions',
        ];
    }
}
