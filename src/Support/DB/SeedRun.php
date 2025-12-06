<?php
namespace Catch\Support\DB;

use Catch\CatchAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Seed 执行
 */
class SeedRun
{
    /**
     * 执行类
     *
     * @param string $module
     * @param string|null $seeder
     * @return bool
     * @throws Throwable
     */
    public static function run(string $module, ?string $seeder): bool
    {
        $files = File::allFiles(CatchAdmin::getModuleSeederPath($module));
        DB::transaction(function () use ($files, $seeder) {
            foreach ($files as $file) {
                $class = require_once $file->getRealPath();

                if (pathinfo($file->getBasename(), PATHINFO_FILENAME) == $seeder) {
                    $seeder = new $class();
                    $seeder->run();
                    break;
                }
                $seeder = new $class();
                $seeder->run();
            }
        });
        return true;
    }
}
