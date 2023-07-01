<?php

namespace Catch\Events\Excel;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Excel Export Event
 */
class Export
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $file;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $file)
    {
        //
        $this->file = $file;
    }
}
