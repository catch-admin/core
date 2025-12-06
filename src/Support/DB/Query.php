<?php

namespace Catch\Support\DB;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class Query
{
    protected static ?string $log = null;

    public static function listen(): void
    {
        try {
            DB::listen(function ($query) {
                $date = date('Y-m-d H:i');
                $sql = "[$date] {$query->sql} | {$query->time} ms \n";
                foreach ($query->bindings as $binding) {
                    $sql = preg_replace('/\?/', $binding, $sql, 1);
                }
                static::$log .= $sql;
            });
        } catch (Throwable|Exception $e) {
        }
    }

    public static function log(): void
    {
        if (static::$log) {
            Log::channel(config('catch.query_log_channel', 'query'))->info(static::$log);

            static::$log = null;
        }
    }
}
