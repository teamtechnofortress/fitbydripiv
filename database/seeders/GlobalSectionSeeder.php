<?php

namespace Database\Seeders;

use App\Services\Content\GlobalSectionService;
use Illuminate\Database\Seeder;

class GlobalSectionSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(GlobalSectionService::class);

        foreach (config('global_sections', []) as $key => $definition) {
            $service->save([
                'key' => $key,
                'config' => $definition['config'] ?? [],
            ]);
        }
    }
}
