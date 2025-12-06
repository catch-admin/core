<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://catchadmin.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/yanwenwu/catch-admin/blob/master/LICENSE.txt )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Catch\Enums;

enum Status: int implements Enum
{
    case Enable = 1;

    case Disable = 2;

    /**
     * @desc name
     */
    public function name(): string
    {
        return match ($this) {
            Status::Enable => '启用',

            Status::Disable => '禁用'
        };
    }

    /**
     * get value
     */
    public function value(): int
    {
        return match ($this) {
            Status::Enable => 1,

            Status::Disable => 2,
        };
    }

    /**
     * 断言
     *
     * @param $value
     * @return bool
     */
    public function assert($value): bool
    {
        return $this->value() == $value;
    }
}
