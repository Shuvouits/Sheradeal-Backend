<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontCollectionController extends Controller
{
    public function __construct(
        private CollectionService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Collections retrieved successfully.',
            'data' => $this->service->storefrontList(),
        ]);
    }

    public function show(
        Request $request,
        string $slug
    ): JsonResponse {
        $validated = $request->validate([
            'limit' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ]);

        $limit = (int) ($validated['limit'] ?? 24);

        return response()->json([
            'success' => true,
            'message' => 'Collection retrieved successfully.',
            'data' => $this->service->storefrontShow(
                $slug,
                $limit
            ),
        ]);
    }
}
