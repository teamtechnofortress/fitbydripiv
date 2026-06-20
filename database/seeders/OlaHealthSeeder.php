<?php

namespace Database\Seeders;

use App\Models\DrNetwork;
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
        DB::transaction(function (): void {
            DrNetwork::query()
                ->where('slug', 'ola-health')
                ->delete();
        });
    }
}
