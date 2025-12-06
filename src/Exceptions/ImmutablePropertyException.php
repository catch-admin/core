<?php

declare(strict_types=1);

namespace Catch\Exceptions;

final class ImmutablePropertyException extends CatchException
{
    public function __construct(string $property)
    {
        parent::__construct("Cannot update immutable property: {$property}");
    }
}
