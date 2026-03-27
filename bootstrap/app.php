<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt' => \PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate::class,
            'auth.customer' => \App\Http\Middleware\CustomerAuthMiddleware::class,
        ]);
        $middleware->append(\App\Http\Middleware\ForceJsonResponse::class);
        $middleware->append(\App\Http\Middleware\ApiLogger::class);
        $middleware->append(\App\Http\Middleware\AuditLogMiddleware::class);
        // $middleware->throttleApi('60', 'api');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return true; // Always render JSON
        });

        // JWT Clean Errors
        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
                'valid_token' => false
            ], 401);
        });

        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid',
                'valid_token' => false
            ], 401);
        });

        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has been blacklisted',
                'valid_token' => false
            ], 401);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e) {
            $prev = $e->getPrevious();
            if ($prev instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json(['success' => false, 'message' => 'Token has expired', 'valid_token' => false], 401);
            }
            if ($prev instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json(['success' => false, 'message' => 'Token is invalid', 'valid_token' => false], 401);
            }
            if ($prev instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException) {
                return response()->json(['success' => false, 'message' => 'Token has been blacklisted', 'valid_token' => false], 401);
            }
            
            // Check message for "not provided" or other cases
            $message = $e->getMessage();
            if (str_contains(strtolower($message), 'not provided')) {
                return response()->json(['success' => false, 'message' => 'Token not provided', 'valid_token' => false], 401);
            }

            return response()->json(['success' => false, 'message' => 'Unauthorized access', 'valid_token' => false], 401);
        });
    })->create();
