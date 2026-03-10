<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTextRewardReport extends Model
{
    use HasFactory;
    protected $table = 'email_text_reward_report';

    protected $fillable = [
        'frequency',
        'range_sdate',
        'range_edate',
        'email_sent',
        'text_sent',
        'reward_sent',
        'birth_sent',        
        'email',
        'reported_date',
        'deleted',
    ];
}
