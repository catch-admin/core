<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 ~ now https://catchadmin.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

namespace Catch\Commands;

use Catch\Base\CatchModel;
use Catch\CatchAdmin;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportMenuCommand extends CatchCommand
{
    protected $signature = 'catch:export:menu {--p : 是否使用树形结构} {--module= : 指定导出的模块}';

    protected $description = 'catch export table data';

    protected function initialize(InputInterface $input, OutputInterface $output): void {}

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        if ($moduleOfOption = $this->option('module')) {
            $module = [$moduleOfOption];
        } else {
            $modules = CatchAdmin::getAllModules();

            try {
                $selectedModulesTitle = $this->choice(
                    '选择导出菜单的模块',
                    $modules->pluck('title')->toArray(),
                    attempts: 1,
                    multiple: true
                );
            } catch (\Exception $e) {
                $this->error('未选择任何模块');
                exit;
            }

            $module = [];
            $modules->each(function ($item) use ($selectedModulesTitle, &$module) {
                if (in_array($item['title'], $selectedModulesTitle)) {
                    $module[] = $item['name'];
                }
            });
        }

        $model = $this->createModel();

        $data = $model->whereIn('module', $module)->get()->toTree();
        $data = 'return '.var_export(json_decode($data, true), true).';';
        $this->exportSeed($data, $module);

        $this->info('模块菜单导出成功');
    }

    protected function exportSeed($data, $module): void
    {
        $module = $module[0];

        $stub = File::get(__DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'menuSeeder.stub');

        $class = ucfirst($module).'MenusSeeder';

        $stub = str_replace('{CLASS}', $class, $stub);

        File::put(CatchAdmin::getModuleSeederPath($module).$class.'.php', str_replace('{menus}', $data, $stub));
    }

    protected function createModel(): CatchModel
    {
        return new class extends CatchModel
        {
            protected $table = 'permissions';
        };
    }
}
