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
        protected Builder $builder
    ){}

    /**
     * macros boot
     */
    public function boot(): void
    {
        $this->builder->boot();
        $this->collection->boot();
        $this->builder->boot();
    }
}
