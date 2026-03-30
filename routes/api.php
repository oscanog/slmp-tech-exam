<?php

use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

$resources = [
    'users' => UserController::class,
    'posts' => PostController::class,
    'comments' => CommentController::class,
    'albums' => AlbumController::class,
    'photos' => PhotoController::class,
    'todos' => TodoController::class,
];

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::middleware(['auth:api', CheckToken::using('resources:read')])->group(function () use ($resources) {
    foreach ($resources as $uri => $controller) {
        Route::get($uri, [$controller, 'index']);
        Route::get($uri.'/{id}', [$controller, 'show'])->whereNumber('id');
    }
});

Route::middleware(['auth:api', CheckToken::using('resources:write')])->group(function () use ($resources) {
    foreach ($resources as $uri => $controller) {
        Route::post($uri, [$controller, 'store']);
        Route::match(['put', 'patch'], $uri.'/{id}', [$controller, 'update'])->whereNumber('id');
        Route::delete($uri.'/{id}', [$controller, 'destroy'])->whereNumber('id');
    }
});
