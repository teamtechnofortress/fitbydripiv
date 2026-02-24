<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\ReactivationNotification;

class NotifyUserReactivated
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $event->user->notify(new ReactivationNotification($event->user));
    }
}
