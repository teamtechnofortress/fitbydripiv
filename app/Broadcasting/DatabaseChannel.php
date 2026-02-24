<?php

namespace App\Broadcasting;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel as DBO;

class DatabaseChannel extends DBO
{
    
     // here we are overriding the default laravel DatabaseChannel
     // in order to serialize notifications being stored in the database  
          
    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        return [
            'id' => $notification->id,
            'type' => get_class($notification),
            'data' => ['data' => serialize($notification)],
            'read_at' => null,
            'serialized' => true
        ];
    }
}
