<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // CmsSeeder::class,
            SettingSeeder::class,
            GlobalSectionSeeder::class,
            AboutPageSeeder::class,
            HomePageSeeder::class,
            CategoryTemplateSeeder::class,
            GeneralFaqPageSeeder::class,
            PrivacyPageSeeder::class,
            ProductSelectionPageSeeder::class,
            TelehealthFaqPageSeeder::class,
            TermsPageSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
