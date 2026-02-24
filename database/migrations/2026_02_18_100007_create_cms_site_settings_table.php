<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_video_url')->nullable();
            $table->string('hero_poster_image')->nullable();
            $table->decimal('hero_video_playback_speed', 3, 1)->default(1.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_site_settings');
    }
};
