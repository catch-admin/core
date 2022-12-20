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

trait ScopeTrait
{
    /**
     * creator
     */
    public static function scopeCreator($query): void
    {
        $model = app(static::class);

        if (in_array($model->getCreatorIdColumn(), $model->getFillable())) {
                $userModel = app(getAuthUserModel());

            $query->addSelect([
                    'creator' => $userModel->whereColumn($model->getCreatorIdColumn(), $userModel->getTable() . '.' . $userModel->getKeyName())->select('username')->limit(1)
                ]);
        }
    }
}
