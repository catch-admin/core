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

declare(strict_types=1);

namespace Catch;

use Catch\Contracts\ModuleRepositoryInterface;
use Catch\Support\Module\Installer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CatchAdmin
{
    public const VERSION = '1.0.0';

    /**
     * version
     */
    public static function version(): string
    {
        return static::VERSION;
    }

    public static function moduleRoot(): string
    {
        return config('catch.module.root', 'modules/');
    }

    /**
     * module root path
     */
    public static function moduleRootPath(): string
    {
        return self::makeDir(base_path(self::moduleRoot()).DIRECTORY_SEPARATOR);
    }

    /**
     * make dir
     */
    public static function makeDir(string $dir): string
    {
        if (! File::isDirectory($dir) && ! File::makeDirectory($dir, 0777, true)) {
            throw new \RuntimeException(sprintf('Directory %s created Failed', $dir));
        }

        return $dir;
    }

    /**
     * module dir
     */
    public static function getModulePath(string $module, bool $make = true): string
    {
        if ($make) {
            return self::makeDir(self::moduleRootPath().ucfirst($module).DIRECTORY_SEPARATOR);
        }

        return self::moduleRootPath().ucfirst($module).DIRECTORY_SEPARATOR;
    }

    /**
     * delete module path
     */
    public static function deleteModulePath(string $module): bool
    {
        if (self::isModulePathExist($module)) {
            File::deleteDirectory(self::getModulePath($module));
        }

        return true;
    }

    /**
     * module path exists
     */
    public static function isModulePathExist(string $module): bool
    {
        return File::isDirectory(self::moduleRootPath().ucfirst($module).DIRECTORY_SEPARATOR);
    }

    /**
     * module migration dir
     */
    public static function getModuleMigrationPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR);
    }

    /**
     * module seeder dir
     */
    public static function getModuleSeederPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'database'.DIRECTORY_SEPARATOR.'seeder'.DIRECTORY_SEPARATOR);
    }

    /**
     * get modules dir
     */
    public static function getModulesPath(): array
    {
        return File::directories(self::moduleRootPath());
    }

    /**
     * get module root namespace
     */
    public static function getModuleRootNamespace(): string
    {
        return config('catch.module.namespace', 'Modules').'\\';
    }

    /**
     * get module root namespace
     */
    public static function getModuleNamespace($moduleName): string
    {
        if (! self::isModulePathExist($moduleName)) {
            return ltrim($moduleName, '\\') . '\\';
        }

        return self::getModuleRootNamespace().ucfirst($moduleName).'\\';
    }

    /**
     * model namespace
     */
    public static function getModuleModelNamespace($moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Models\\';
    }

    /**
     * getServiceProviders
     */
    public static function getModuleServiceProviderNamespace($moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Providers\\';
    }

    public static function getModuleServiceProvider($moduleName): string
    {
        return self::getModuleServiceProviderNamespace($moduleName).ucfirst($moduleName).'ServiceProvider';
    }

    /**
     * controller namespace
     */
    public static function getModuleControllerNamespace($moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Http\\Controllers\\';
    }

    /**
     * getModuleRequestNamespace
     */
    public static function getModuleRequestNamespace($moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Http\\Requests\\';
    }

    /**
     * getModuleRequestNamespace
     */
    public static function getModuleEventsNamespace($moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Events\\';
    }

    /**
     * getModuleRequestNamespace
     */
    public static function getModuleListenersNamespace($moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Listeners\\';
    }

    /**
     * module provider dir
     */
    public static function getModuleProviderPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Providers'.DIRECTORY_SEPARATOR);
    }

    /**
     * module model dir
     */
    public static function getModuleModelPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Models'.DIRECTORY_SEPARATOR);
    }

    /**
     * module controller dir
     */
    public static function getModuleControllerPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR);
    }

    /**
     * module request dir
     */
    public static function getModuleRequestPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Http'.DIRECTORY_SEPARATOR.'Requests'.DIRECTORY_SEPARATOR);
    }

    /**
     * module request dir
     */
    public static function getModuleEventPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Events'.DIRECTORY_SEPARATOR);
    }

    /**
     * module request dir
     */
    public static function getModuleListenersPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Listeners'.DIRECTORY_SEPARATOR);
    }

    /**
     * commands path
     */
    public static function getCommandsPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Commands'.DIRECTORY_SEPARATOR);
    }

    /**
     * commands namespace
     */
    public static function getCommandsNamespace(string $module): string
    {
        return self::getModuleNamespace($module).'Commands\\';
    }

    /**
     * module route
     */
    public static function getModuleRoutePath(string $module, string $routeName = 'route.php'): string
    {
        $path = self::getModulePath($module).'routes'.DIRECTORY_SEPARATOR;

        self::makeDir($path);

        return $path.$routeName;
    }

    /**
     * module route.php exists
     */
    public static function isModuleRouteExists(string $module): bool
    {
        return File::exists(self::getModuleRoutePath($module));
    }

    /**
     * relative path
     */
    public static function getModuleRelativePath($path): string
    {
        return Str::replaceFirst(base_path(), '.', $path);
    }

    public static function getModuleInstaller(string $module): Installer
    {
        $installer = self::getModuleNamespace($module).'Installer';

        if (class_exists($installer)) {
            return app($installer);
        }

        throw new \RuntimeException("Installer [$installer] Not Found");
    }

    /**
     * get module
     */
    public static function parseFromRouteAction(): array
    {
        [$controllerNamespace, $action] = explode('@', Route::currentRouteAction());

        $controllerNamespace = Str::of($controllerNamespace)->lower()->remove('controller')->explode('\\');

        $controller = $controllerNamespace->pop();

        $module = $controllerNamespace->get(1);

        return [$module, $controller, $action];
    }

    /**
     * @throws \ReflectionException
     */
    public static function getControllerActions(string $module, string $controller): array
    {
        $controller = self::getModuleControllerNamespace($module).Str::of($controller)->ucfirst()->append('Controller')->toString();

        $reflectionClass = new \ReflectionClass($controller);

        $actions = [];

        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->isPublic() && ! $method->isConstructor()) {
                $actions[] = $method->getName();
            }
        }

        return $actions;
    }

    /**
     * @throws BindingResolutionException
     */
    public static function getAllModules(): mixed
    {
        return app()->make(ModuleRepositoryInterface::class)->all();
    }

    /**
     * @return array
     */
    public static function getAllProviders(): array
    {
        $dirs = File::directories(self::moduleRootPath());

        $providers = [];
        foreach ($dirs as $dir) {
            $provider = self::getModuleServiceProvider(pathinfo($dir, PATHINFO_BASENAME));
            if (class_exists($provider)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }
}
