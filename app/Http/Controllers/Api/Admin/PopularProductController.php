<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\PopularProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PopularProductController extends Controller
{
    public function __construct(
        private PopularProductService $service
    ) {}

    public function update(Request $request, int $product): JsonResponse
    {
        $validated = $request->validate([
            'is_popular' => ['required', 'boolean'],
            'popular_sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ]);

        $updated = $this->service->update(
            $product,
            (bool) $validated['is_popular'],
            (int) ($validated['popular_sort_order'] ?? 0)
        );

        return response()->json([
            'success' => true,
            'message' => 'Product popularity updated successfully.',
            'data' => $updated,
        ]);
    }
}
