<?php

namespace Catch\Support;

use Exception;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Log;
use Throwable;

class HttpKernel extends Kernel
{
    public function terminate($request, $response): void
    {
        try {
            parent::terminate($request, $response);
        } catch (Throwable|Exception $e) {
            //
            Log::error('terminate exception: '.$e->getMessage());
        }
    }
}
