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
