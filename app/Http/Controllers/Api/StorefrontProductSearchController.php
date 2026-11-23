<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StorefrontProductSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontProductSearchController extends Controller
{
    public function __construct(
        private StorefrontProductSearchService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:200',
            ],
            'sort' => [
                'nullable',
                'in:relevance,newest,price_asc,price_desc,name_asc',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:48',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data' => $this->service->search($filters),
        ]);
    }
}
