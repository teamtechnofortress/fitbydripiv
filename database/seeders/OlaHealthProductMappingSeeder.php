<?php

namespace Database\Seeders;

use App\Models\DrNetwork;
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

        $mappings = [
            'semaglutide' => 'weight-loss-semaglutide-injection',
            'tirzepatide' => 'weight-loss-tirzepatide-injection',
            'glutathione' => 'wellness-glutathione-injection',
            'b12-injection' => 'wellness-b12-methylcobalamin-injection',
            'nad-therapy' => 'wellness-nad-plus-therapy',
        ];

        $seededCount = 0;

        foreach ($mappings as $productSlug => $identifier) {
            $product = Product::where('slug', $productSlug)->first();

            if (! $product) {
                $this->command?->warn("Product with slug [{$productSlug}] not found. Skipping.");

                continue;
            }

            NetworkProductMapping::updateOrCreate(
                [
                    'dr_network_id' => $olaHealth->id,
                    'product_id' => $product->id,
                ],
                [
                    'identifier' => $identifier,
                    'is_active' => true,
                ]
            );

            $seededCount++;
        }

        Log::info('Ola Health product mappings seeded', ['count' => $seededCount]);
    }
}
