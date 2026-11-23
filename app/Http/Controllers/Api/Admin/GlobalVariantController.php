<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GlobalVariantRequest;
use App\Services\GlobalVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalVariantController extends Controller
{
    public function __construct(
        protected GlobalVariantService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Global variants retrieved successfully.',
            'data' => $this->service->list(
                $validated['search'] ?? null
            ),
        ]);
    }

    public function show(int $variant): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Global variant retrieved successfully.',
            'data' => $this->service->find($variant),
        ]);
    }

    public function store(
        GlobalVariantRequest $request
    ): JsonResponse {
        $variant = $this->service->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Global variant created successfully.',
            'data' => $variant,
        ], 201);
    }

    public function update(
        GlobalVariantRequest $request,
        int $variant
    ): JsonResponse {
        $updated = $this->service->update(
            $variant,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Global variant updated successfully.',
            'data' => $updated,
        ]);
    }

    public function reorder(
        Request $request,
        int $variant
    ): JsonResponse {
        $validated = $request->validate([
            'option_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'option_ids.*' => [
                'required',
                'integer',
                'distinct',
            ],
        ]);

        $updated = $this->service->reorderOptions(
            $variant,
            $validated['option_ids']
        );

        return response()->json([
            'success' => true,
            'message' => 'Variant options reordered successfully.',
            'data' => $updated,
        ]);
    }

    public function destroy(int $variant): JsonResponse
    {
        $this->service->delete($variant);

        return response()->json([
            'success' => true,
            'message' => 'Global variant deleted successfully.',
        ]);
    }
}
