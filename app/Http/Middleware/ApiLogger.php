<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $log = [
            'IP' => $request->ip(),
            'Method' => $request->method(),
            'URL' => $request->fullUrl(),
            'Request' => $request->except(['password', 'password_hash']),
            'Status' => $response->getStatusCode(),
        ];

        Log::info('API Request Logged:', $log);

        return $response;
    }
}
