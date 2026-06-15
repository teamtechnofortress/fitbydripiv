<?php

namespace Database\Seeders;

use App\Models\CmsCategory;
use App\Models\CmsSiteSetting;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        CmsSiteSetting::updateOrCreate(['id' => 1], [
            'hero_video_url' => null,
            'hero_poster_image' => null,
            'hero_video_playback_speed' => 1.0,
        ]);

        CmsCategory::updateOrCreate(['slug' => 'weight-loss'], [
            'name' => 'Weight Loss',
            'description' => 'Prescription weight loss solutions customized to your goals. Evidence-based treatments delivered to your door.',
            'banner_image' => 'https://images.pexels.com/photos/4498606/pexels-photo-4498606.jpeg?auto=compress&cs=tinysrgb&w=1920',
            'background_video' => '/weight-loss.mp4',
            'video_playback_speed' => 1.0,
            'display_order' => 1,
        ]);

        CmsCategory::updateOrCreate(['slug' => 'wellness'], [
            'name' => 'Wellness',
            'description' => 'Support for body and mind wellness. Personalized treatments for optimal health and vitality.',
            'banner_image' => 'https://images.pexels.com/photos/3768916/pexels-photo-3768916.jpeg?auto=compress&cs=tinysrgb&w=1920',
            'background_video' => '/wellness.mp4',
            'video_playback_speed' => 1.0,
            'display_order' => 2,
        ]);

        CmsCategory::updateOrCreate(['slug' => 'longevity'], [
            'name' => 'Longevity',
            'description' => 'Cellular health and longevity solutions. Science-backed treatments for healthy aging.',
            'banner_image' => 'https://images.pexels.com/photos/3984366/pexels-photo-3984366.jpeg?auto=compress&cs=tinysrgb&w=1920',
            'background_video' => '/longevity.mp4',
            'video_playback_speed' => 1.0,
            'display_order' => 3,
        ]);
    }
}
