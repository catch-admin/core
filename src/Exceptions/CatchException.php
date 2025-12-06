<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://catchadmin.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Catch\Exceptions;

use Catch\Enums\Code;
use Catch\Enums\Enum;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class CatchException extends HttpException
{
    protected $code = 0;

    public function __construct(string $message = '', int|Code $code = 0)
    {
        if ($code instanceof Enum) {
            $code = $code->value();
        }

        if ($this->code instanceof Enum && ! $code) {
            $code = $this->code->value();
        }

        parent::__construct($this->statusCode(), $message ?: $this->message, null, [], $code);
    }

    /**
     * status code
     */
    public function statusCode(): int
    {
        return 500;
    }

    /**
     * render
     */
    public function render(): array
    {
        return [
            'code' => $this->code,

            'message' => $this->message,
        ];
    }
}
