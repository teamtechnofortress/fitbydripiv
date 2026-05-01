<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsCategory extends Model
{
    use HasUuids;

    protected $table = 'cms_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'banner_image',
        'landscape_banner',
        'background_video',
        'video_playback_speed',
        'display_order',
    ];

    protected $casts = [
        'video_playback_speed' => 'float',
        'display_order' => 'integer',
    ];


}
