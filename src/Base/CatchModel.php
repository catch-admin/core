<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2022 https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Catch\Base;

use Catch\Support\DB\SoftDelete;
use Catch\Traits\DB\BaseOperate;
use Catch\Traits\DB\ScopeTrait;
use Catch\Traits\DB\Trans;
use Catch\Traits\DB\WithAttributes;
use Catch\Traits\DB\WithSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use DateTimeInterface;
use Carbon\Carbon;

/**
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
abstract class CatchModel extends Model
{
    use WithAttributes, BaseOperate, Trans, SoftDeletes, ScopeTrait;

    /**
     * unix timestamp
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * paginate limit
     */
    protected $perPage = 10;

    /**
     * @var string[]
     */
    protected array $defaultCasts = [
        'created_at' => 'datetime:Y-m-d H:i',

        'updated_at' => 'datetime:Y-m-d H:i',
    ];

    protected array $defaultHidden = ['deleted_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->init();
    }

    /**
     * init
     */
    protected function init()
    {
        $this->makeHidden($this->defaultHidden);

        $this->mergeCasts($this->defaultCasts);

        // auto use data range
        foreach (class_uses_recursive(static::class) as $trait) {
            if (str_contains($trait, 'DataRange')) {
                $this->setDataRange();
            }
        }
    }

    /**
     * soft delete
     *
     * @time 2021年08月09日
     * @return void
     */
    public static function bootSoftDeletes(): void
    {
        static::addGlobalScope(new SoftDelete());
    }

    /**
     * 重写 serializeDate
     *
     * @param DateTimeInterface $date
     * @return string|null
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return Carbon::instance($date)->toISOString(true);
    }
}
