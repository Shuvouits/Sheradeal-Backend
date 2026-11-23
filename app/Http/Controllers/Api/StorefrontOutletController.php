<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StoreOutletService;
use Illuminate\Http\JsonResponse;

class StorefrontOutletController extends Controller
{
    public function __construct(
        private StoreOutletService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->published(),
        ]);
    }
}
