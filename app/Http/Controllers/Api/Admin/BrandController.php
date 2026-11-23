<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function __construct(
        protected BrandService $brandService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => [
                'nullable',
                Rule::in([
                    'all',
                    'pending',
                    'active',
                    'featured',
                    'inactive',
                    'rejected',
                    'archived',
                ]),
            ],
            'source' => [
                'nullable',
                Rule::in(['official', 'vendor']),
            ],
            'sort' => [
                'nullable',
                Rule::in([
                    'latest',
                    'oldest',
                    'name_asc',
                    'name_desc',
                    'display_order',
                ]),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brands retrieved successfully.',
            'data' => $this->brandService->list($filters),
        ]);
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Brand statistics retrieved successfully.',
            'data' => $this->brandService->statistics(),
        ]);
    }

    public function show(int $brand): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Brand retrieved successfully.',
            'data' => $this->brandService->find($brand),
        ]);
    }

    public function store(BrandRequest $request): JsonResponse
    {
        $brand = $this->brandService->create(
            $request->validated(),
            (int) auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully.',
            'data' => $brand,
        ], 201);
    }

    public function update(
        BrandRequest $request,
        int $brand
    ): JsonResponse {
        $updated = $this->brandService->update(
            $brand,
            $request->validated(),
            (int) auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Brand updated successfully.',
            'data' => $updated,
        ]);
    }

    public function toggleFeatured(int $brand): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Featured status updated successfully.',
            'data' => $this->brandService->toggleFeatured($brand),
        ]);
    }

    public function archive(int $brand): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Brand archived successfully.',
            'data' => $this->brandService->archive($brand),
        ]);
    }

    public function restoreArchive(int $brand): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Brand restored from archive.',
            'data' => $this->brandService->restoreArchive($brand),
        ]);
    }

    public function destroy(int $brand): JsonResponse
    {
        $this->brandService->delete($brand);

        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully.',
        ]);
    }

    public function restoreDeleted(int $brand): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Deleted brand restored successfully.',
            'data' => $this->brandService->restoreDeleted($brand),
        ]);
    }

    public function uploadLogo(
        Request $request,
        int $brand
    ): JsonResponse {
        $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $updated = $this->brandService->uploadLogo(
            $brand,
            $request->file('logo')
        );

        return response()->json([
            'success' => true,
            'message' => 'Brand logo uploaded successfully.',
            'data' => $updated,
            'logo_url' => asset('storage/' . $updated->logo),
        ]);
    }
}
