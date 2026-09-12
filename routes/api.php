<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CouponsController;
use App\Http\Controllers\OccasionsController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\TemplatesController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/occasions', [OccasionsController::class, 'occasionView']);
Route::get('/occasions/{id}', [OccasionsController::class, 'occasionViewDetail']);
Route::get('/templates', [TemplatesController::class, 'index']);
Route::get('/templates/{id}', [TemplatesController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);

    Route::middleware('role:customer')->group(function () {
        Route::get('/projects', [ProjectsController::class, 'index']);
        Route::post('/projects', [ProjectsController::class, 'store']);
        Route::get('/projects/{id}', [ProjectsController::class, 'show']);
        Route::post('/projects/{id}', [ProjectsController::class, 'update']);
        Route::get('/projects/{id}/uploads', [ProjectsController::class, 'uploads']);
        Route::post('/projects/{id}/media', [ProjectsController::class, 'uploadMedia']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [UsersController::class, 'index']);
        Route::post('/users/{id}', [UsersController::class, 'update']);
        Route::delete('/users/{id}', [UsersController::class, 'destroy']);

        Route::get('/occasions', [OccasionsController::class, 'occasionView']);
        Route::post('/occasions', [OccasionsController::class, 'occasionCreate']);
        Route::post('/occasions/bulk-create', [OccasionsController::class, 'occasionBulkCreate']);
        Route::get('/occasions/{id}', [OccasionsController::class, 'occasionViewDetail']);
        Route::post('/occasions/{id}', [OccasionsController::class, 'occasionUpdate']);
        Route::post('/occasions/{id}/media', [OccasionsController::class, 'occasionUpload']);
        Route::delete('/occasions/{id}', [OccasionsController::class, 'occasionDelete']);

        Route::get('/templates', [TemplatesController::class, 'adminIndex']);
        Route::post('/templates', [TemplatesController::class, 'store']);
        Route::post('/templates/bulk-create', [TemplatesController::class, 'bulkCreate']);
        Route::get('/templates/{id}', [TemplatesController::class, 'adminShow']);
        Route::post('/templates/{id}', [TemplatesController::class, 'update']);
        Route::post('/templates/{id}/content', [TemplatesController::class, 'updateContent']);
        Route::get('/templates/{id}/uploads', [TemplatesController::class, 'uploads']);
        Route::post('/templates/{id}/media', [TemplatesController::class, 'uploadMedia']);
        Route::post('/templates/{id}/cover', [TemplatesController::class, 'uploadCover']);
        Route::delete('/templates/{id}', [TemplatesController::class, 'destroy']);

        Route::get('/coupons', [CouponsController::class, 'index']);
        Route::post('/coupons', [CouponsController::class, 'store']);
        Route::get('/coupons/{id}', [CouponsController::class, 'show']);
        Route::post('/coupons/{id}', [CouponsController::class, 'update']);
        Route::delete('/coupons/{id}', [CouponsController::class, 'destroy']);
    });
});
