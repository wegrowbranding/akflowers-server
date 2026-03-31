<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class DeliveryStaffAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Tell Laravel to use the delivery_api guard for this request lifecycle
        auth()->shouldUse('delivery_api');

        try {
            if (! JWTAuth::parseToken()->authenticate()) {
                throw new UnauthorizedHttpException('jwt-auth', 'Unauthorized access');
            }
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('jwt-auth', $e->getMessage(), $e);
        }

        return $next($request);
    }
}
