<?php

namespace Catch\Facade;

use Illuminate\Support\Facades\Facade;
use Catch\Support\Admin as AdminSupport;

/**
 * @method static auth()
 * @method static id()
 * @method static logout()
 * @method static currentLoginUser()
 * @method static clearUserPersonalToken($tokenId = null)
 * @method static clearAllCachedUsers()
 */
class Admin extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AdminSupport::class;
    }
}
