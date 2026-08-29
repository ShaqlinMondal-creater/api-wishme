<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'WishMe API is working!'
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');