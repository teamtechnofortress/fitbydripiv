<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardReport extends Model
{
    use HasFactory;
    protected $table = 'reward_report';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',
        'totalRewardPurchases',
        'rewardGold',
        'rewardSilver',
        'rewardBronze',
        'rewardDiscount',        
        'email',
        'reported_date',
        'deleted',
    ];
}
