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

class AuthMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        try {
            if (! $user = Auth::guard(getGuardName())->user()) {
                throw new AuthenticationException();
            }

            Event::dispatch(new UserEvent($user));

            return $next($request);
        } catch (Exception|Throwable $e) {
            throw new FailedException(Code::LOST_LOGIN->message().":{$e->getMessage()}", Code::LOST_LOGIN);
        }
    }
}
