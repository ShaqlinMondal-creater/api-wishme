<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('wishme.name').' API',
        'brand' => config('wishme.brand'),
        'health' => url('/api/health'),
    ]);
});