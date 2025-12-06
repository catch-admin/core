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

namespace Catch\Providers;

use Catch\CatchAdmin;
use Catch\Contracts\ModuleRepositoryInterface;
use Catch\Exceptions\Handler;
use Catch\Support\DB\Query;
use Catch\Support\Macros\MacrosRegister;
use Catch\Support\Module\ModuleManager;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

/**
 * CatchAmin Service Provider
 */
class CatchAdminServiceProvider extends ServiceProvider
{
    /**
     * boot
     *
     * @throws ContainerExceptionInterface
     */
    public function boot(): void
    {
        $this->bootMacros();
        $this->bootDefaultModuleProviders();
        $this->bootModuleProviders();
        $this->registerEvents();
        $this->listenDBLog();
    }

    /**
     * register
     *
     * @throws ReflectionException
     */
    public function register(): void
    {
        $this->registerCommands();
        $this->registerModuleRepository();
        $this->registerExceptionHandler();
        $this->publishConfig();
        $this->publishModuleMigration();
    }

    /**
     * @throws BindingResolutionException
     */
    protected function bootMacros(): void
    {
        $this->app->make(MacrosRegister::class)->boot();
        // 资源路由注册器
        $this->app->bind(ResourceRegistrar::class, \Catch\Support\ResourceRegistrar::class);
    }

    /**
     * register commands
     *
     * @throws ReflectionException
     */
    protected function registerCommands(): void
    {
        loadCommands(dirname(__DIR__).DIRECTORY_SEPARATOR.'Commands', 'Catch\\');
    }

    /**
     * bind module repository
     */
    protected function registerModuleRepository(): void
    {
        // register module manager
        $this->app->singleton(ModuleManager::class, function () {
            return new ModuleManager(fn () => Container::getInstance());
        });

        // register module repository
        $this->app->singleton(ModuleRepositoryInterface::class, function () {
            return $this->app->make(ModuleManager::class)->driver();
        });

        $this->app->alias(ModuleRepositoryInterface::class, 'module');
    }

    /**
     * register events
     */
    protected function registerEvents(): void
    {
        Event::listen(RequestHandled::class, config('catch.response.request_handled_listener'));
    }

    /**
     * register exception handler
     */
    protected function registerExceptionHandler(): void
    {
        if (isRequestFromDashboard()) {
            $this->app->singleton(ExceptionHandler::class, function () {
                return new Handler((fn () => Container::getInstance()));
            });
        }
    }


    /**
     * publish config
     */
    protected function publishConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $from = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'catch.php';

            $to = config_path('catch.php');

            $this->publishes([$from => $to], 'catch-config');
        }
    }

    /**
     * publish module migration
     */
    protected function publishModuleMigration(): void
    {
        if ($this->app->runningInConsole()) {
            $form = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2022_11_14_034127_module.php';

            $to = database_path('migrations').DIRECTORY_SEPARATOR.'2022_11_14_034127_module.php';

            $this->publishes([$form => $to], 'catch-module');
        }
    }

    protected function bootDefaultModuleProviders(): void
    {
        foreach ($this->app['config']->get('catch.module.default', []) as $module) {
            $provider = CatchAdmin::getModuleServiceProvider($module);
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * boot module
     * @throws BindingResolutionException
     */
    protected function bootModuleProviders(): void
    {
        // 如果配置了模块自动加载，则不需要从本地配置中加载
        if (config('catch.module.autoload')) {
            foreach (CatchAdmin::getAllProviders() as $provider) {
                $this->app->register($provider);
            }
        } else {
            foreach ($this->app->make(ModuleRepositoryInterface::class)->getEnabled() as $module) {
                if (class_exists($module['provider'])) {
                    $this->app->register($module['provider']);
                }
            }
        }

        $this->registerModuleRoutes();
    }

    /**
     * register module routes
     */
    protected function registerModuleRoutes(): void
    {
        if (! $this->app->routesAreCached()) {
            $route = $this->app['config']->get('catch.route', []);

            if (! empty($route) && isset($route['prefix'])) {
                Route::prefix($route['prefix'])
                    ->middleware($route['middlewares'])
                    ->group($this->app['config']->get('catch.module.routes'));
            }
        }
    }

    /**
     * listen db log
     */
    protected function listenDBLog(): void
    {
        if ($this->app['config']->get('catch.listen_db_log')) {
            Query::listen();

            $this->app->terminating(function () {
                Query::log();
            });
        }
    }

    /**
     * file exist
     */
    protected function routesAreCached(): bool
    {
        return $this->app->routesAreCached();
    }
}
