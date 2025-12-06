<?php

declare(strict_types=1);

namespace Catch\Support\Macros;

use Illuminate\Routing\Router as LaravelRouter;

class Router
{
    public function boot(): void
    {
        $this->adminResource();
    }

    /**
     * admin 资源路由
     */
    protected function adminResource(): void
    {
        LaravelRouter::macro('adminResource', function ($name, $controller, array $options = []) {

            $only = ['index', 'show', 'store', 'update', 'destroy', 'enable', 'export', 'import', 'form', 'table', 'restore', 'dynamic'];

            $filterOnly = function (array $only) use ($controller) {
                $newOnly = [];
                $controller = new \ReflectionClass($controller);
                $methods = $controller->getMethods();
                foreach ($methods as $method) {
                    if ($method->isPublic() && in_array($name = $method->getName(), $only)) {
                        $newOnly[] = $name;
                    }
                }

                return $newOnly;
            };

            $only = $filterOnly($only);

            if (isset($options['except'])) {
                $only = array_diff($only, (array) $options['except']);
            }

            return $this->resource($name, $controller, array_merge([
                'only' => $only,
            ], $options));
        });
    }
}
