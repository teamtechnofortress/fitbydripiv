<?php

namespace App\Traits;

use App\Models\DatabaseNotification;
use Illuminate\Notifications\Notifiable as Notifier;

trait Notifiable
{
    use Notifier;

    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }
    
}