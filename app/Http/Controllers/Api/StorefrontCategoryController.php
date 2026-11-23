<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StorefrontCategoryService;
use Illuminate\Http\JsonResponse;

class StorefrontCategoryController extends Controller
{
    public function __construct(
        private StorefrontCategoryService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getCategories(),
        ]);
    }

    public function featured(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getCategories(true),
        ]);
    }
}
