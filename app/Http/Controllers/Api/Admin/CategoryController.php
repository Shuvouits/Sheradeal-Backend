<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],

            'filter' => [
                'nullable',
                Rule::in(['all', 'active', 'featured', 'inactive']),
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

            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (
                        $value !== 'root' &&
                        !(ctype_digit((string) $value) && (int) $value > 0)
                    ) {
                        $fail('Invalid parent category filter.');
                    }
                },
            ],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => $this->categoryService->list($filters),
        ]);
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Category statistics retrieved successfully.',
            'data' => $this->categoryService->statistics(),
        ]);
    }

    public function tree(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Category tree retrieved successfully.',
            'data' => $this->categoryService->tree(),
        ]);
    }

    public function show(int $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully.',
            'data' => $this->categoryService->find($category),
        ]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create(
            $request->validated(),
            (int) auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function update(
        CategoryRequest $request,
        int $category
    ): JsonResponse {
        $updated = $this->categoryService->update(
            $category,
            $request->validated(),
            (int) auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $updated,
        ]);
    }

    public function toggleFeatured(int $category): JsonResponse
    {
        $updated = $this->categoryService->toggleFeatured(
            $category,
            (int) auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Featured status updated successfully.',
            'data' => $updated,
        ]);
    }

    public function uploadCover(
        Request $request,
        int $category
    ): JsonResponse {
        $request->validate([
            'cover' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:4096',
            ],
        ]);

        $updated = $this->categoryService->uploadMedia(
            $category,
            $request->file('cover'),
            'cover_image',
            (int) auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category cover uploaded successfully.',
            'data' => $updated,
            'cover_url' => asset('storage/' . $updated->cover_image),
        ]);
    }

    public function uploadIcon(
        Request $request,
        int $category
    ): JsonResponse {
        $request->validate([
            'icon' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],
        ]);

        $updated = $this->categoryService->uploadMedia(
            $category,
            $request->file('icon'),
            'icon',
            (int) auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category icon uploaded successfully.',
            'data' => $updated,
            'icon_url' => asset('storage/' . $updated->icon),
        ]);
    }

    public function destroy(int $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function restore(int $category): JsonResponse
    {
        $restored = $this->categoryService->restore($category);

        return response()->json([
            'success' => true,
            'message' => 'Category restored successfully.',
            'data' => $restored,
        ]);
    }
}
