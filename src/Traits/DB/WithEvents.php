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

namespace Catch\Traits\DB;

use Closure;

/**
 * base operate
 */
trait WithEvents
{
    protected ?Closure $beforeGetList = null;

    protected ?Closure $afterFirstBy = null;

    /**
     * @return $this
     */
    public function setBeforeGetList(Closure $closure): static
    {
        $this->beforeGetList = $closure;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAfterFirstBy(Closure $closure): static
    {
        $this->afterFirstBy = $closure;

        return $this;
    }
}
