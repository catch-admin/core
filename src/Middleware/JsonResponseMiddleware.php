<?php

namespace Catch\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JsonResponseMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);

        // set expose header，download excel needs
        $response->headers->set('Access-Control-Expose-Headers', 'filename,write_type');

        // binary file response
        if ($response instanceof BinaryFileResponse) {
            return $response;
        }

        // other response
        if ($response instanceof Response) {
            return new JsonResponse($response->getContent());
        }

        return $response;
    }
}
