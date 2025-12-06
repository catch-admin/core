<?php

namespace Catch\Enums;

interface Enum
{
    public function value(): int|string;

    public function name(): string;
}
