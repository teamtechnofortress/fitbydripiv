<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialPromo extends Model
{
    use HasFactory;

    protected $fillable = [
        'promoTitle',
        'discountJoin',
        'discountForSilver',
        'volumeToSilver',
        'discountForBronze',
        'volumeToBronze',
        'discountForGold',
        'volumeToGold',
    ];

    protected $casts = [

    ];
}
