<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PopularProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontPopularProductController extends Controller
{
    public function __construct(
        private PopularProductService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'between:1,24'],
        ]);

        $limit = (int) ($validated['limit'] ?? 12);

        return response()->json([
            'success' => true,
            'message' => 'Popular products retrieved successfully.',
            'data' => $this->service->list($limit),
        ]);
    }
}
