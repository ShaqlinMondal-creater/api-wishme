<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::middleware('role:customer')->group(function () {
        Route::get('/projects', [ProjectsController::class, 'index']);
        Route::post('/projects', [ProjectsController::class, 'store']);
        Route::get('/projects/{id}', [ProjectsController::class, 'show']);
        Route::put('/projects/{id}', [ProjectsController::class, 'update']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [UsersController::class, 'index']);
        Route::put('/users/{id}', [UsersController::class, 'update']);
        Route::delete('/users/{id}', [UsersController::class, 'destroy']);
    });
});
