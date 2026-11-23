<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CollectionRequest;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function __construct(
        private CollectionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:all,active,draft'],
            'collection_type' => [
                'nullable',
                'in:manual,automated',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Collections retrieved successfully.',
            'data' => $this->service->list($filters),
        ]);
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->statistics(),
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'between:1,50',
            ],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->service->searchProducts($filters),
        ]);
    }

    public function store(CollectionRequest $request): JsonResponse
    {
        $collection = $this->service->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Collection created successfully.',
            'data' => $collection,
        ], 201);
    }

    public function show(int $collection): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->show($collection),
        ]);
    }

    public function update(
        CollectionRequest $request,
        int $collection
    ): JsonResponse {
        $updated = $this->service->update(
            $collection,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Collection updated successfully.',
            'data' => $updated,
        ]);
    }

    public function uploadCover(
        Request $request,
        int $collection
    ): JsonResponse {
        $validated = $request->validate([
            'cover_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $updated = $this->service->uploadCover(
            $collection,
            $validated['cover_image']
        );

        return response()->json([
            'success' => true,
            'message' => 'Collection image uploaded successfully.',
            'data' => $updated,
        ]);
    }

    public function removeCover(int $collection): JsonResponse
    {
        $updated = $this->service->removeCover($collection);

        return response()->json([
            'success' => true,
            'message' => 'Collection image removed successfully.',
            'data' => $updated,
        ]);
    }

    public function destroy(int $collection): JsonResponse
    {
        $this->service->delete($collection);

        return response()->json([
            'success' => true,
            'message' => 'Collection deleted successfully.',
        ]);
    }
}
