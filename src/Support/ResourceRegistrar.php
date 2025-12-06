<?php

namespace Catch\Support;

use Illuminate\Routing\ResourceRegistrar as LaravelResourceRegistrar;
use Illuminate\Routing\Route;

class ResourceRegistrar extends LaravelResourceRegistrar
{
    protected $resourceDefaults = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'enable', 'export', 'import', 'form', 'table', 'restore', 'dynamic'];

    /**
     * 添加 enable 对应的路由
     *
     * @param $name
     * @param $base
     * @param $controller
     * @param $options
     * @return Route
     */
    protected function addResourceEnable($name, $base, $controller, $options): Route
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name).'/enable/{'.$base.'}';

        $action = $this->getResourceAction($name, $controller, 'enable', $options);

        return $this->router->put($uri, $action);
    }

    /**
     * 添加 export 对应的路由
     *
     * @param $name
     * @param $base
     * @param $controller
     * @param $options
     * @return Route
     */
    protected function addResourceExport($name, $base, $controller, $options): Route
    {
        $uri = 'export/' . $this->getResourceUri($name);

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'export', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * 添加 import 对应的路由
     *
     * @param $name
     * @param $base
     * @param $controller
     * @param $options
     * @return Route
     */
    protected function addResourceImport($name, $base, $controller, $options): Route
    {
        $uri = 'import/' . $this->getResourceUri($name);

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'import', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * 添加 form 对应的路由
     *
     * @param $name
     * @param $base
     * @param $controller
     * @param $options
     * @return Route
     */
    protected function addResourceForm($name, $base, $controller, $options): Route
    {
        $uri = 'form/'. $this->getResourceUri($name);

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'form', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * 添加 dynamic 对应的路由
     *
     * @param $name
     * @param $base
     * @param $controller
     * @param $options
     * @return Route
     */
    protected function addResourceDynamic($name, $base, $controller, $options): Route
    {
        $uri = $this->getResourceUri($name).'/dynamic/r';

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'dynamic', $options);

        return $this->router->get($uri, $action);
    }


    /**
     * 添加 table 对应的路由
     *
     * @param $name
     * @param $base
     * @param $controller
     * @param $options
     * @return Route
     */
    protected function addResourceTable($name, $base, $controller, $options): Route
    {
        $uri = 'table/'. $this->getResourceUri($name);

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'table', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * 添加 table 对应的路由
     *
     * @param $name
     * @param $base
     * @param $controller
     * @param $options
     * @return Route
     */
    protected function addResourceRestore($name, $base, $controller, $options): Route
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name).'/restore/{'.$base.'}';

        $action = $this->getResourceAction($name, $controller, 'restore', $options);

        return $this->router->put($uri, $action);
    }
}
