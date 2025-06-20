<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Facades\LogBatch;

class LogSuccessfulLogin
{
    /**
     * Flag statis untuk memastikan log hanya dijalankan sekali per request.
     * @var bool
     */
    private static $hasLoggedIn = false;
    
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
    public function handle(Login $event): void
    {
        if (!self::$hasLoggedIn) {
            activity('Authentication')
                ->performedOn($event->user)
                ->causedBy($event->user)
                ->log('User logged in');

            self::$hasLoggedIn = true;
        }
    }
}
