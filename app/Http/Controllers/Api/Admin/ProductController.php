<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Product Listing
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],

            'status' => [
                'nullable',
                Rule::in([
                    'all',
                    'active',
                    'draft',
                    'archived',
                ]),
            ],

            'category_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'brand_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'product_type' => [
                'nullable',
                Rule::in([
                    'physical',
                    'digital',
                    'service',
                ]),
            ],

            'is_featured' => [
                'nullable',
                'boolean',
            ],

            'publish_online' => [
                'nullable',
                'boolean',
            ],

            'sort_by' => [
                'nullable',
                Rule::in([
                    'id',
                    'title',
                    'price',
                    'quantity',
                    'status',
                    'created_at',
                    'updated_at',
                ]),
            ],

            'sort_direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data' => $this->productService->paginate($filters),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Statistics
    |--------------------------------------------------------------------------
    */

    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Product statistics retrieved successfully.',
            'data' => $this->productService->statistics(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Single Product
    |--------------------------------------------------------------------------
    */

    public function show(int $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => $this->productService->find($product),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Product
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreProductRequest $request
    ): JsonResponse {
        $product = $this->productService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $product,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateProductRequest $request,
        int $product
    ): JsonResponse {
        $updated = $this->productService->update(
            $product,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $updated,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Product
    |--------------------------------------------------------------------------
    */

    public function destroy(int $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Product
    |--------------------------------------------------------------------------
    */

    public function restore(int $product): JsonResponse
    {
        $restored = $this->productService->restore($product);

        return response()->json([
            'success' => true,
            'message' => 'Product restored successfully.',
            'data' => $restored,
        ]);
    }
}
