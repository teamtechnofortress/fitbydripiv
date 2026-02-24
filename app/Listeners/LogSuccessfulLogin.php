<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogSuccessfulLogin
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
        LoginHistory::create([
            'user_id' => $event->user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'logged_in_at' => now(),
        ]);
    }
}
