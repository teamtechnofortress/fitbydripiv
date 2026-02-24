<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogSuccessfulLogout
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
        if(!empty($event->user->id)) {
            LoginHistory::where('user_id', $event->user->id)
                ->whereNull('logged_out_at')
                ->latest('logged_in_at')
                ->first()?->update(['logged_out_at' => now()]);
        }        
    }
}
