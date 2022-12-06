<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 ~ now https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

namespace Catch\Commands;

use Catch\CatchAdmin;
use Catch\Facade\Module;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleInstallCommand extends CatchCommand
{
    protected $signature = 'catch:module:install {module} {--f}';

    protected $description = 'install catch module';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if (! $this->option('f')) {
            if ($input->hasArgument('module')
                && Module::getEnabled()->pluck('name')->merge(Collection::make(config('catch.module.default')))->contains(lcfirst($input->getArgument('module')))
            ) {
                $this->error(sprintf('Module [%s] Has installed', $input->getArgument('module')));
                exit;
            }
        }
    }

    public function handle(): void
    {
        $installer = CatchAdmin::getModuleInstaller($this->argument('module'));

        $installer->install();
    }
}
