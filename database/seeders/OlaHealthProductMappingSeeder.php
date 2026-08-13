<?php

namespace Database\Seeders;

use App\Models\DrNetwork;
use App\Models\NetworkFlowDefinition;
use App\Models\NetworkProductMapping;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class OlaHealthProductMappingSeeder extends Seeder
{
    public function run(): void
    {
        $olaHealth = DrNetwork::where('slug', 'ola-health')->first();

        if (! $olaHealth) {
            $this->command?->warn('Ola Health network not found. Skipping product mappings.');

            return;
        }

        $flows = NetworkFlowDefinition::query()
            ->forNetwork($olaHealth->id)
            ->whereIn('flow_key', [
                OlaHealthNetworkSeeder::ASYNC_FLOW_KEY,
                OlaHealthNetworkSeeder::VIDEO_FLOW_KEY,
                // OlaHealthNetworkSeeder::FOLLOW_UP_ASYNC_FLOW_KEY,
            ])
            ->get()
            ->keyBy('flow_key');

        NetworkProductMapping::query()
            ->where('dr_network_id', $olaHealth->id)
            ->whereNull('flow_id')
            ->delete();

        $mappings = $this->mappings();

        $seededCount = 0;

        foreach ($mappings as $mapping) {
            $productSlug = $mapping['product_slug'];
            $product = Product::where('slug', $productSlug)->first();

            if (! $product) {
                $this->command?->warn("Product with slug [{$productSlug}] not found. Skipping.");

                continue;
            }

            $flow = $flows->get($mapping['flow_key']);

            if (! $flow) {
                $this->command?->warn("Flow with key [{$mapping['flow_key']}] not found. Skipping.");

                continue;
            }

            NetworkProductMapping::updateOrCreate(
                [
                    'dr_network_id' => $olaHealth->id,
                    'product_id' => $product->id,
                    'flow_id' => $flow->id,
                ],
                [
                    'external_service_id' => $mapping['external_service_id'],
                    'external_service_key' => $mapping['external_service_key'],
                    'external_config' => $mapping['external_config'],
                    'is_active' => true,
                ]
            );

            $seededCount++;
        }

        Log::info('Ola Health product mappings seeded', ['count' => $seededCount]);
    }

    private function mappings(): array
    {
        $initialMappings = [
            [
                'product_slug' => 'b12-injection',
                'service_name' => 'B-Complex',
                'service_key' => 'fitbyshot-bcomplex',
                'service_id' => '1780',
                'protocol' => 'glutathione_mic_b12_initial',
            ],
            [
                'product_slug' => 'glutathione',
                'service_name' => 'Glutathione',
                'service_key' => 'fitbyshot-glutathoine',
                'service_id' => '1778',
                'protocol' => 'glutathione_mic_b12_initial',
            ],
            [
                'product_slug' => 'nad-therapy',
                'service_name' => 'NAD+',
                'service_key' => 'fitbyshot-nad',
                'service_id' => '1777',
                'protocol' => 'nad_initial',
            ],
            [
                'product_slug' => 'tirzepatide',
                'service_name' => 'Tirzepatide Injection',
                'service_key' => 'fitbyshot-tirzepatide-injection',
                'service_id' => '1775',
                'protocol' => 'glp1_initial',
            ],
            [
                'product_slug' => 'semaglutide',
                'service_name' => 'Semaglutide Injection',
                'service_key' => 'fitbyshot-semaglutide-injection',
                'service_id' => '1659',
                'protocol' => 'glp1_initial',
            ],
        ];

        return collect($initialMappings)
            ->flatMap(fn (array $mapping): array => $this->initialFlowMappings($mapping))
            // Follow-up async product mappings are temporarily disabled.
            // ->merge([
            //     $this->mapping(
            //         'tirzepatide',
            //         OlaHealthNetworkSeeder::FOLLOW_UP_ASYNC_FLOW_KEY,
            //         '1776',
            //         'fitbyshot-tirzepatide-injection',
            //         'follow-up-async',
            //         'Tirzepatide Injection - Follow Up'
            //     ),
            //     $this->mapping(
            //         'semaglutide',
            //         OlaHealthNetworkSeeder::FOLLOW_UP_ASYNC_FLOW_KEY,
            //         '1782',
            //         'fitbyshot-semaglutide-injection',
            //         'follow-up-async',
            //         'Semaglutide Injection - Follow Up'
            //     ),
            // ])
            ->all();
    }

    private function initialFlowMappings(array $mapping): array
    {
        return array_map(
            fn (string $flowKey): array => $this->mapping(
                $mapping['product_slug'],
                $flowKey,
                $mapping['service_id'],
                $mapping['service_key'],
                'initial',
                $mapping['service_name'],
                $mapping['protocol']
            ),
            [OlaHealthNetworkSeeder::ASYNC_FLOW_KEY, OlaHealthNetworkSeeder::VIDEO_FLOW_KEY]
        );
    }

    private function mapping(
        string $productSlug,
        string $flowKey,
        string $serviceId,
        string $serviceKey,
        string $sessionType,
        string $serviceName,
        string $protocol = 'initial'
    ): array {
        return [
            'product_slug' => $productSlug,
            'flow_key' => $flowKey,
            'external_service_id' => $serviceId,
            'external_service_key' => $serviceKey,
            'external_config' => [
                'service_name' => $serviceName,
                'session_type' => $sessionType,
                'protocol' => $protocol,
            ],
        ];
    }
}
