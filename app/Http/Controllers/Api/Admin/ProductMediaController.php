<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductMediaController extends Controller
{
    public function __construct(
        protected ProductMediaService $mediaService
    ) {}

    public function index(int $product): JsonResponse
    {
        $media = $this->mediaService->list($product);

        return response()->json([
            'success' => true,
            'message' => 'Product media retrieved successfully.',
            'data' => $media,
        ]);
    }

    public function store(
        Request $request,
        int $product
    ): JsonResponse {
        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:5120',
            ],
        ]);

        $media = $this->mediaService->upload(
            $product,
            $validated['image']
        );

        return response()->json([
            'success' => true,
            'message' => 'Product image uploaded successfully.',
            'data' => $media,
        ], 201);
    }

    public function cover(
        int $product,
        int $media
    ): JsonResponse {
        $result = $this->mediaService->setCover(
            $product,
            $media
        );

        return response()->json([
            'success' => true,
            'message' => 'Cover image updated successfully.',
            'data' => $result,
        ]);
    }

    public function destroy(
        int $product,
        int $media
    ): JsonResponse {
        $this->mediaService->delete(
            $product,
            $media
        );

        return response()->json([
            'success' => true,
            'message' => 'Product image deleted successfully.',
        ]);
    }
}
