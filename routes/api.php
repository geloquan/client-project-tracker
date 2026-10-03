<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('guest');

    Route::middleware('auth')->group(function (): void {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::patch('profile', [AuthController::class, 'updateProfile']);
        Route::put('profile/password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('projects/{project}/transition', [ProjectController::class, 'transition']);
        Route::apiResource('projects', ProjectController::class);
    });
});
