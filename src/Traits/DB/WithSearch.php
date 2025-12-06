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

/**
 * base operate
 */
trait WithSearch
{
    public array $searchable = [];

    public ?\Closure $quickSearchCallback = null;

    /**
     * @return $this
     */
    public function setSearchable(array $searchable): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * 设置快速搜索回调，用于转换数据
     *
     * @return $this
     */
    public function setQuickSearchCallback(\Closure $callback): static
    {
        $this->quickSearchCallback = $callback;

        return $this;
    }
}
