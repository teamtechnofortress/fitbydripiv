<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GlobalSection extends Model
{
    use HasUuids;

    protected $table = 'global_sections';

    protected $fillable = [
        'key',
        'type',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}
