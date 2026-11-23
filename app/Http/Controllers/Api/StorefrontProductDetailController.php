<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductDetailService;
use Illuminate\Http\JsonResponse;

class StorefrontProductDetailController extends Controller
{
    public function __construct(
        private ProductDetailService $service
    ) {}

    public function show(string $slug): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Product details retrieved successfully.',
            'data' => $this->service->show($slug),
        ]);
    }
}
