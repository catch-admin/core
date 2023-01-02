<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2022 https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Catch\Base;

use Catch\Enums\Code;
use Catch\Exceptions\FailedException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * base catch controller
 */
abstract class CatchController extends Controller
{
    /**
     * @param string|null $guard
     * @param string|null $field
     * @return mixed
     */
    protected function getLoginUser(string|null $guard = null,  string|null $field = null): mixed

    {
        $user = Auth::guard($guard ?: getGuardName())->user();

        if (! $user) {
            throw new FailedException('登录失效, 请重新登录', Code::LOST_LOGIN);
        }

        if ($field) {
            return $user->getAttribute($field);
        }

        return $user;
    }


    /**
     * @param $guard
     * @return mixed
     */
    protected function getLoginUserId($guard = null): mixed
    {
        return $this->getLoginUser($guard, 'id');
    }
}
