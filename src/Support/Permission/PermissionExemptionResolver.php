<?php

declare(strict_types=1);

namespace Catch\Support\Permission;

use Catch\Attributes\PermissionExempt;
use Illuminate\Http\Request;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class PermissionExemptionResolver
{
    public function isExempt(Request $request): bool
    {
        $route = $request->route();

        if (! $route) {
            return false;
        }

        $action = $route->getActionName();

        if (! is_string($action) || ! str_contains($action, '@')) {
            return false;
        }

        [$controller, $method] = explode('@', $action, 2);

        if ($controller === '' || $method === '') {
            return false;
        }

        try {
            $reflectionClass = new ReflectionClass($controller);
            $reflectionMethod = $reflectionClass->getMethod($method);
        } catch (ReflectionException) {
            return false;
        }

        $methodAttribute = $this->resolveAttribute($reflectionMethod);

        if ($methodAttribute) {
            return $this->matches($methodAttribute->methods, $request->method());
        }

        $classAttribute = $this->resolveAttribute($reflectionClass);

        if ($classAttribute) {
            return $this->matches($classAttribute->methods, $request->method());
        }

        return false;
    }

    private function resolveAttribute(ReflectionClass|ReflectionMethod $reflection): ?PermissionExempt
    {
        $attributes = $reflection->getAttributes(PermissionExempt::class, ReflectionAttribute::IS_INSTANCEOF);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    private function matches(string|array|null $methods, string $requestMethod): bool
    {
        if ($methods === null) {
            return true;
        }

        $normalizedMethods = array_map(
            static fn (string $method): string => strtoupper($method),
            is_array($methods) ? $methods : [$methods]
        );

        return in_array(strtoupper($requestMethod), $normalizedMethods, true);
    }
}
