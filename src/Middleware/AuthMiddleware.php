<?php

namespace Catch\Middleware;

use Catch\Enums\Code;
use Catch\Events\User as UserEvent;
use Catch\Exceptions\FailedException;
use Catch\Exceptions\LostLoginException;
use Catch\Exceptions\TokenExpiredException;
use Catch\Facade\Admin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class AuthMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        try {
            $user = Admin::auth();
        } catch (AuthenticationException $e) {
            throw new LostLoginException('身份认证过期或失败');
        } catch (TokenExpiredException $e) {
            throw new LostLoginException('Token 已过期');
        }

        if ($user) {
            Event::dispatch(new UserEvent($user));
        }

        return $next($request);
    }
}
