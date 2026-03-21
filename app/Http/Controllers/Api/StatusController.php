<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends BaseController
{
    /**
     * Get API status.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->sendResponse([
            'status' => 'online',
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
        ], 'API server is running smoothly.');
    }

    /**
     * Health check endpoint.
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'uptime' => floor(microtime(true) - LARAVEL_START),
        ]);
    }
}
