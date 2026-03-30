<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'application' => config('app.name'),
        'status' => 'ok',
        'message' => 'Use the /api endpoints for authentication and resource access.',
    ]);
});
