<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WishmeService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(WishmeService $wishme): JsonResponse
    {
        return response()->json($wishme->health());
    }
}
