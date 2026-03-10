<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsSiteSetting extends Model
{
    protected $table = 'cms_site_settings';

    protected $fillable = [
        'hero_video_url',
        'hero_poster_image',
        'hero_video_playback_speed',
    ];

    protected $casts = [
        'hero_video_playback_speed' => 'float',
    ];
}
