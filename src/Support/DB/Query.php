<?php

namespace Catch\Support\DB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Query
{
    /**
     * @var string|null
     */
    protected static string|null $log = null;

    /**
     * @return void
     */
    public static function listen(): void
    {
        DB::listen(function ($query) {
            $formattedString = sprintf('[%s] %s | %s ms'.PHP_EOL,
                date('Y-m-d H:i'),
                $query->sql,
                $query->time
            );

            static::$log .= Str::of($formattedString)->replaceArray('?', $query->bindings)->toString();
        });
    }


    /**
     * @return void
     */
    public static function log(): void
    {
        if (static::$log) {
            $sqlLogPath = storage_path('logs'.DIRECTORY_SEPARATOR.'query'.DIRECTORY_SEPARATOR);

            if (! File::isDirectory($sqlLogPath)) {
                File::makeDirectory($sqlLogPath, 0777, true);
            }

            $logFile = $sqlLogPath.date('Ymd').'.log';

            if (! File::exists($logFile)) {
                File::put($logFile, '', true);
            }

            file_put_contents($logFile, static::$log.PHP_EOL, LOCK_EX | FILE_APPEND);

            static::$log = null;
        }
    }
}
