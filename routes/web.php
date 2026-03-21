<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the API Server',
        'api_version' => 'v1',
        'api_status_check' => '/api/v1/status',
        'status' => 'active'
    ]);
});
