<?php

namespace Catch\Middleware;

use Catch\Enums\Code;
use Catch\Events\User as UserEvent;
use Catch\Exceptions\FailedException;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Throwable;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        try {
            $guardName = getGuardName();

            if (! $user = Auth::guard($guardName)->user()) {
                throw new AuthenticationException();
            }

            Event::dispatch(new UserEvent($user));

            return $next($request);
        } catch (Exception|Throwable $e) {
            if ($e instanceof TokenExpiredException) {
                throw new FailedException(Code::LOGIN_EXPIRED->message(), Code::LOGIN_EXPIRED);
            }

            if ($e instanceof TokenBlacklistedException) {
                throw new FailedException(Code::LOGIN_BLACKLIST->message(), Code::LOGIN_BLACKLIST);
            }

            throw new FailedException(Code::LOST_LOGIN->message().":{$e->getMessage()}", Code::LOST_LOGIN);
        }
    }
}
