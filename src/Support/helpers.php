<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------
declare(strict_types=1);

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\VarDumper\VarDumper;
use Catch\Base\CatchModel;

/**
 * load commands
 */
if (! function_exists('loadCommands')) {
    /**
     * @throws ReflectionException
     */
    function loadCommands($paths, $namespace, $searchPath = null): void
    {
        if (! $searchPath) {
            $searchPath = dirname($paths).DIRECTORY_SEPARATOR;
        }

        $paths = Collection::make(Arr::wrap($paths))->unique()->filter(function ($path) {
            return is_dir($path);
        });

        if ($paths->isEmpty()) {
            return;
        }

        foreach ((new Finder())->in($paths->toArray())->files() as $command) {
            $command = $namespace.str_replace(['/', '.php'], ['\\', ''], Str::after($command->getRealPath(), $searchPath));

            if (is_subclass_of($command, Command::class) &&
                ! (new ReflectionClass($command))->isAbstract()) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }
}

/**
 * table prefix
 */
if (! function_exists('withTablePrefix')) {
    function withTablePrefix(string $table): string
    {
        return DB::connection()->getTablePrefix().$table;
    }
}

/**
 * get guard name
 */
if (! function_exists('getGuardName')) {
    function getGuardName(): string
    {
        $guardKeys = array_keys(config('catch.auth.guards'));

        if (count($guardKeys)) {
            return $guardKeys[0];
        }

        return 'admin';
    }
}

/**
 * get table columns
 */
if (! function_exists('getTableColumns')) {
    function getTableColumns(string $table): array
    {
        $SQL = 'desc '.withTablePrefix($table);

        $columns = [];

        foreach (DB::select($SQL) as $column) {
            $columns[] = $column->Field;
        }

        return $columns;
    }
}

if (! function_exists('dd_')) {
    /**
     * @param mixed ...$vars
     * @return never
     */
    function dd_(...$vars): never
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');

        dd(...$vars);
    }
}

if (! function_exists('getAuthUserModel')) {
    /**
     * get user model
     *
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    function getAuthUserModel(): mixed
    {
        return config(
            'catch.auth.providers.'.
            config('catch.auth.guards.admin.provider').'.model'
        );
    }
}


if (! function_exists('importTreeData')) {
    /**
     * import tree data
     *
     * @param array $data
     * @param string $table
     * @param string $pid
     * @param string $primaryKey
     */
    function importTreeData(array $data, string $table, string $pid = 'parent_id', string $primaryKey = 'id'): void
    {
        foreach ($data as $value) {
            if (isset($value[$primaryKey])) {
                unset($value[$primaryKey]);
            }

            $children = $value['children'] ?? false;
            if($children) {
                unset($value['children']);
            }

            // 首先查询是否存在
            $model = new class extends CatchModel {};

            $menu = $model->setTable($table)->where('permission_name', $value['permission_name'])
                ->where('module', $value['module'])
                ->where('permission_mark', $value['permission_mark'])
                ->first();

            if ($menu) {
                $id = $menu->id;
            } else {
                $id = DB::table($table)->insertGetId($value);
            }
            if ($children) {
                foreach ($children as &$v) {
                    $v[$pid] = $id;
                }

                importTreeData($children, $table, $pid);
            }
        }
    }
}
