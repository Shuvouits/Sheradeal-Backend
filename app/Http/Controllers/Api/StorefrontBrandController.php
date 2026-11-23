<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StorefrontBrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontBrandController extends Controller
{
    public function __construct(
        private StorefrontBrandService $service
    ) {}

    public function show(
        Request $request,
        string $slug
    ): JsonResponse {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:48',
            ],

            'sort' => [
                'sometimes',
                'in:newest,oldest,price_asc,price_desc,name_asc,name_desc',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brand products retrieved successfully.',
            'data' => $this->service->show(
                $slug,
                $validated
            ),
        ]);
    }
}
