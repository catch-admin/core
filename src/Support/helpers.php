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
declare(strict_types=1);

use Catch\Base\CatchModel;
use Illuminate\Console\Application;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Illuminate\Database\Connection;

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

        foreach ((new Finder)->in($paths->toArray())->files() as $command) {
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
        return config('catch.auth', 'admin');
    }
}

/**
 * @param  Closure  $callback
 * @param  string  $table
 *                         获取表结构字段
 */
if (! function_exists('getTableColumns')) {
    function getTableColumns(string $table, ?Closure $callback = null): array
    {
        $table = withTablePrefix($table);

        // 默认使用mysql SQL
        if (! $callback) {
            $SQL = 'desc '.$table;
        } else {
            // 其他驱动，自定义 callback 使用
            // getTableColumns('table', function ($table) {
            // return Other SQL
            // }
            $SQL = $callback($table);
        }

        $columns = [];

        foreach (DB::select($SQL) as $column) {
            $columns[] = $column->Field;
        }

        return $columns;
    }
}

if (! function_exists('dd_')) {
    /**
     * @param  mixed  ...$vars
     *
     * @throws \Laravel\Octane\Exceptions\DdException
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
     */
    function getAuthUserModel(): mixed
    {
        return config('catch.auth_model');
    }
}

if (! function_exists('importTreeData')) {
    /**
     * import tree data
     */
    function importTreeData(array $data, string $table, string $pid = 'parent_id', string $primaryKey = 'id'): void
    {
        foreach ($data as $value) {
            if (isset($value[$primaryKey])) {
                unset($value[$primaryKey]);
            }

            $children = $value['children'] ?? false;
            if ($children) {
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

if (! function_exists('isRequestFromDashboard')) {

    function isRequestFromDashboard(): bool
    {
        return Request::hasHeader('Request-from')
            && Str::of(Request::header('Request-from'))->lower()->exactly('dashboard');
    }
}

if (! function_exists('command')) {
    function command(string|array $command): void
    {
        $exec = function ($command) {
            if (Str::of(PHP_OS)->lower()->contains('dar')) {
                exec(Application::formatCommandString($command));
            } else {
                Process::run(Application::formatCommandString($command))->throw();
            }
        };

        if (is_array($command)) {
            foreach ($command as $c) {
                $exec($c);
            }
        } else {
            $exec($command);
        }
    }
}

/**
 * 加载 cms 资源
 */
if (! function_exists('cms_asset')) {
    function cms_asset(string $asset)
    {
        return Vite::cmsAsset($asset);
    }
}

/**
 * 格式化返回
 */
if (! function_exists('format_response_data')) {
    function format_response_data(mixed $data): array
    {
        $responseData = [];

        if ($data instanceof LengthAwarePaginator) {
            $responseData['data'] = $data->items();
            $responseData['total'] = $data->total();
            $responseData['limit'] = $data->perPage();
            $responseData['page'] = $data->currentPage();

            return $responseData;
        } else {
            if (is_object($data)
                && property_exists($data, 'per_page')
                && property_exists($data, 'total')
                && property_exists($data, 'current_page')) {
                $responseData['data'] = $data->data;
                $responseData['total'] = $data->total;
                $responseData['limit'] = $data->per_page;
                $responseData['page'] = $data->current_page;

                return $responseData;
            }
        }

        $responseData['data'] = $data;

        return $responseData;
    }
}

/**
 * 后台缓存方法，可以集中管理后台缓存
 */
if (! function_exists('admin_cache')) {
    function admin_cache(string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, Closure $callback)
    {
        $cacheKey = config('catch.admin_cache_key').$key;

        if ($ttl === null || $ttl === 0) {
            return Cache::forever($cacheKey, $callback());
        }

        return Cache::remember($key, $ttl, $callback);
    }
}

/**
 * 后台缓存方法，可以集中管理后台缓存
 */
if (! function_exists('admin_cache_get')) {
    function admin_cache_get(string $key, mixed $default = null)
    {
        return Cache::get(config('catch.admin_cache_key').$key, $default);
    }
}

/**
 * 后台缓存方法，可以集中管理后台缓存
 */
if (! function_exists('admin_cache_has')) {
    function admin_cache_has(string $key): bool
    {
        return Cache::has(config('catch.admin_cache_key').$key);
    }
}

/**
 * 后台缓存删除方法，可以集中管理后台缓存
 */
if (! function_exists('admin_cache_delete')) {
    function admin_cache_delete(string $key): bool
    {
        return Cache::forget(config('catch.admin_cache_key').$key);
    }
}

/**
 * 获取所有表
 */
if (! function_exists('get_all_tables')) {
    function get_all_tables(?string $connection = null, bool $removePrefix = true): array
    {
        $connection = DB::connection($connection);
        $databaseName = $connection->getDatabaseName();
        $tables = Schema::getTables(is_pgsql($connection) ? '' : $databaseName);
        if ($removePrefix) {
            $tablePrefix = $connection->getTablePrefix();
            foreach ($tables as &$table) {
                $table['name'] = Str::of($table['name'])->replaceStart($tablePrefix, '')->toString();
            }
        }

        return $tables;
    }
}


/**
 * 是否是 PGSQL 驱动
 */
if (! function_exists('is_pgsql')) {
    function is_pgsql(string|Connection|null $connection = null): bool
    {
        if ($connection instanceof Connection) {
            return $connection->getDriverName() === 'pgsql';
        }

        $connection = DB::connection($connection);

        return $connection->getDriverName() === 'pgsql';
    }
}

/**
 * 移除 app url
 */
if (! function_exists('remove_app_url')) {
    function remove_app_url(?string $url, ?string $appUrl = null): string
    {
        if (! $url) {
            return '';
        }

        return Str::of($url)->replaceFirst($appUrl ?: config('app.url'), '')->toString();
    }
}

