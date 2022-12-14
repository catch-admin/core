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

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
        $this->registering();

        foreach ($this->events as $event => $listener) {
            Event::listen($event, $listener);
        }

        $this->loadModuleRoute();
    }


    protected function registering()
    {
    }

    /**
     * return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function loadModuleRoute(): void
    {
        $routes = $this->app['config']->get('catch.module.routes', []);

        $routes[] = $this->routePath();

        $this->app['config']->set('catch.module.routes', $routes);
    }

    /**
     * route path
     *
     * @return string|array
     */
    abstract protected function routePath(): string | array;
}
