<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Catch\Traits\DB;

/**
 * base operate
 */
trait WithSearch
{
    /**
     * @var array $searchable
     */
    public array $searchable = [];

    /**
     *
     * @param array $searchable
     * @return $this
     */
    public function setSearchable(array $searchable): static
    {
        $this->searchable = array_merge($this->searchable,$searchable);

        return $this;
    }
}
