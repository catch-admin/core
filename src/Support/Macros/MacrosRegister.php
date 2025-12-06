<?php

declare(strict_types=1);

namespace Catch\Support\Macros;

/**
 * boot
 */
class MacrosRegister
{

    public function __construct(
        protected Blueprint $blueprint,
        protected Collection $collection,
        protected Builder $builder,
        protected Router $router,
        protected Cache $cache
    ){}

    /**
     * macros boot
     */
    public function boot(): void
    {
        $this->blueprint->boot();
        $this->collection->boot();
        $this->builder->boot();
        $this->router->boot();
        $this->cache->boot();
    }
}
