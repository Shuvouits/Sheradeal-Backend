<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomepageCategorySectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomepageCategorySectionController extends Controller
{
    public function __construct(
        private HomepageCategorySectionService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->storefront(),
        ]);
    }

    public function manage(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->manage(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $section = $this->service->save(
            $this->validatedData($request)
        );

        return response()->json([
            'success' => true,
            'message' => 'Collection section created successfully.',
            'data' => $section,
        ], 201);
    }

    public function update(
        Request $request,
        int $section
    ): JsonResponse {
        $updated = $this->service->save(
            $this->validatedData($request),
            $section
        );

        return response()->json([
            'success' => true,
            'message' => 'Collection section updated successfully.',
            'data' => $updated,
        ]);
    }

    public function destroy(int $section): JsonResponse
    {
        $this->service->delete($section);

        return response()->json([
            'success' => true,
            'message' => 'Collection section deleted successfully.',
        ]);
    }

    public function availableProducts(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Use the Collections module to manage products.',
        ], 410);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => [
                'required',
                'string',
                'max:160',
            ],

            'collection_id' => [
                'required',
                'integer',
                Rule::exists('collections', 'id'),
            ],

            'product_limit' => [
                'required',
                'integer',
                'between:1,30',
            ],

            'display_order' => [
                'required',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);
    }
}
