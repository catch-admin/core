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

namespace Catch\Providers;

use Catch\CatchAdmin;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Finder\Finder;
use Illuminate\Config\Repository;

abstract class CatchModuleServiceProvider extends ServiceProvider
{
    protected array $events = [];


    /**
     * register
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return void
     */
    public function register(): void
    {
        foreach ($this->events as $event => $listener) {
            Event::listen($event, $listener);
        }

        $this->loadMiddlewares();

        $this->loadModuleRoute();

        $this->loadConfig();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function loadMiddlewares()
    {
        if (! empty($middlewares = $this->middlewares())) {
            $route = $this->app['config']->get('catch.route');

            $route['middlewares']= array_merge($route['middlewares'], $middlewares);

            $this->app['config']->set('catch.route', $route);
        }
    }

    /**
     * load module config
     */
    protected function loadConfig()
    {
        if (! is_dir($configPath = $this->configPath())) {
            return;
        }

        $files = [];
        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $files[str_replace('.php', '', $file->getBasename())] = $file->getRealPath();
        }

        // multi config files
        foreach ($files as $name => $file) {
            $this->app->make('config')->set(sprintf('%s.%s',$this->moduleName(), $name), require $file);
        }
    }

    /**
     *
     * @return array
     */
    protected function middlewares(): array
    {
        return [];
    }

    /**
     * return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function loadModuleRoute(): void
    {
        $routes = $this->app['config']->get('catch.module.routes', []);

        $routes[] = CatchAdmin::getModuleRoutePath($this->moduleName());

        $this->app['config']->set('catch.module.routes', $routes);
    }

    /**
     * route path
     *
     * @return string|array
     */
    abstract protected function moduleName(): string | array;


    /**
     * module config path
     *
     * @return string
     */
    protected function configPath(): string
    {
        return CatchAdmin::getModulePath($this->moduleName()) . 'config' . DIRECTORY_SEPARATOR;
    }
}
