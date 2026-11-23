<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdvertisementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontAdvertisementController extends Controller
{
    public function __construct(
        private AdvertisementService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'between:1,24'],
        ]);

        $limit = (int) ($validated['limit'] ?? 12);

        return response()->json([
            'success' => true,
            'message' => 'Advertisements retrieved successfully.',
            'data' => $this->service->visible($limit),
        ]);
    }
}
