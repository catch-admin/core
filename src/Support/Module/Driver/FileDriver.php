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

namespace Catch\Support\Module\Driver;

use Catch\CatchAdmin;
use Catch\Contracts\ModuleRepositoryInterface;
use Catch\Exceptions\FailedException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * FileDriver
 */
class FileDriver implements ModuleRepositoryInterface
{
    protected string $moduleJson;

    /**
     * construct
     */
    public function __construct()
    {
        $this->moduleJson = storage_path('app') . DIRECTORY_SEPARATOR . 'modules.json';
    }

    /**
     * all
     */
    public function all(array $search = []): Collection
    {
        if (! File::exists($this->moduleJson)) {
            return Collection::make([]);
        }

        if (! Str::length(File::get($this->moduleJson))) {
            return Collection::make([]);
        }

        $modules = Collection::make(\json_decode(File::get($this->moduleJson), true))->values();

        $title = $search['title'] ?? '';

        if (! $title) {
            return $modules;
        }

        return $modules->filter(function ($module) use ($title) {
            return Str::of($module['title'])->contains($title);
        });
    }

    /**
     * create module json
     */
    public function create(array $module): bool
    {
        $modules = $this->all();

        $this->hasSameModule($module, $modules);

        $module['provider'] = sprintf('\\%s', CatchAdmin::getModuleServiceProvider($module['path']));
        $module['version'] = '1.0.0';
        $module['enable'] = true;

        $this->removeDirs($module);

        File::put($this->moduleJson, $modules->push($module)->values()->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return true;
    }

    /**
     * module info
     */
    public function show(string $name): Collection
    {
        foreach ($this->all() as $module) {
            if (Str::of($module['name'])->exactly($name)) {
                return Collection::make($module);
            }
        }

        throw new FailedException("Module [$name] not Found");
    }

    /**
     * update module json
     */
    public function update(string $name, array $module): bool
    {
        File::put($this->moduleJson, $this->all()->map(function ($m) use ($module, $name) {
            if (Str::of($name)->exactly($m['name'])) {
                $m['name'] = $module['name'];
                $m['title'] = $module['title'];
                $m['description'] = $module['description'] ?? '';
                $m['keywords'] = $module['keywords'] ?? '';
                $m['enable'] = $module['enable'];
            }
            $this->removeDirs($m);

            return $m;
        })->values()->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return true;
    }

    /**
     * delete module json
     */
    public function delete(string $name): bool
    {
        File::put($this->moduleJson, $this->all()->filter(function ($module) use ($name) {
            if (! Str::of($name)->exactly($module['name'])) {
                return $module;
            }
        })->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return true;
    }

    /**
     * disable or enable
     */
    public function disOrEnable($name): bool|int
    {
        return File::put($this->moduleJson, $this->all()->map(function ($module) use ($name) {
            if (Str::of($module['name'])->exactly($name)) {
                $module['enable'] = ! $module['enable'];
            }

            return $module;
        })->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * get enabled
     */
    public function getEnabled(): Collection
    {
        // TODO: Implement getEnabled() method.
        return $this->all()->where('enable', true)->values();
    }

    /**
     * enabled
     */
    public function enabled(string $moduleName): bool
    {
        // TODO: Implement enabled() method.
        return $this->getEnabled()->pluck('name')->contains($moduleName);
    }

    protected function hasSameModule(array $module, Collection $modules): void
    {
        if ($modules->count()) {
            if ($modules->pluck('name')->contains($module['name'])) {
                throw new FailedException(sprintf('Module [%s] has been created', $module['name']));
            }
        }
    }

    /**
     * remove dirs
     */
    protected function removeDirs(array &$modules): void
    {
        if ($modules['dirs'] ?? false) {
            unset($modules['dirs']);
        }
    }
}
