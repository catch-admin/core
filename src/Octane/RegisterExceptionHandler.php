<?php

namespace Catch\Octane;

use Carbon\Laravel\ServiceProvider as CarbonServiceProvider;
use Catch\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;

class RegisterExceptionHandler
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle($event): void
    {
        if (isRequestFromDashboard()) {
            $event->sandbox->singleton(ExceptionHandler::class, Handler::class);
        }
    }
}
