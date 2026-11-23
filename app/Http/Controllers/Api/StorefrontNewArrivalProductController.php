<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NewArrivalProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontNewArrivalProductController extends Controller
{
    public function __construct(
        private NewArrivalProductService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'between:1,24'],
        ]);

        $limit = (int) ($validated['limit'] ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'New arrival products retrieved successfully.',
            'data' => $this->service->storefront($limit),
        ]);
    }
}
