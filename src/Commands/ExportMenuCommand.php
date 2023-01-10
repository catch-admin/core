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
use Catch\Support\Tree;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\DB;

class ExportMenuCommand extends CatchCommand
{
    protected $signature = 'catch:export:menu {module} {table} {--p}';

    protected $description = 'catch export table data';


    public function handle(): void
    {
        $module = $this->argument('module');

        $table = $this->argument('table');

        $p = $this->option('p');

        if ($module) {
            $data = DB::table($table)->where('deleted_at', 0)
                ->where('module', $module)
                ->get();
        } else {
            $data = DB::table($table)->where('deleted_at', 0)->get();
        }

        $data = json_decode($data->toJson(), true);

        if ($p) {
            $data = Tree::done($data);
        }

        if ($module) {
            $data = 'return ' . var_export($data, true) . ';';
            $this->exportSeed($data, $module);
        } else {
          file_put_contents(base_path() . DIRECTORY_SEPARATOR . $table . '.php', "<?php\r\n return " . var_export($data, true) . ';');
        }

        $this->info('Export Successful');
    }

    protected function exportSeed($data, $module)
    {

        $stub = File::get(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'menuSeeder.stub');

        $class = ucfirst($module) . 'MenusSeeder';

        $stub = str_replace('{CLASS}', $class, $stub);

        File::put(CatchAdmin::getModuleSeederPath($module) . $class .'.php', str_replace('{menus}', $data, $stub));
    }
}
