<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://catchadmin.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

namespace Catch\Commands\Migrate;

use Catch\CatchAdmin;
use Catch\Commands\CatchCommand;
use Illuminate\Support\Facades\File;

class SeedRun extends CatchCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catch:db:seed {module} {--seeder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'catch db seed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $files = File::allFiles(CatchAdmin::getModuleSeederPath($this->argument('module')));

        $fileNames = [];

        $seeder = $this->option('seeder');

        if ($seeder) {
            foreach ($files as $file) {
                if (pathinfo($file->getBasename(), PATHINFO_FILENAME) == $seeder) {
                    $class = require_once $file->getRealPath();

                    $seeder = new $class();

                    $seeder->run();
                }
            }
        } else {
            foreach ($files as $file) {
                if (File::exists($file->getRealPath())) {
                    $class = require_once $file->getRealPath();
                    $class = new $class();
                    $class->run();
                }
            }
        }

        $this->info('seed run success');
    }
}
