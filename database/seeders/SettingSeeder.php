<?php

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        app(SettingService::class)->ensureDefaults();
    }
}
