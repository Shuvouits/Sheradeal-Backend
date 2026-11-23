<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\NewArrivalProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewArrivalProductController extends Controller
{
    public function __construct(
        private NewArrivalProductService $service
    ) {}

    public function update(Request $request, int $product): JsonResponse
    {
        $validated = $request->validate([
            'is_new_arrival' => ['required', 'boolean'],
        ]);

        $updated = $this->service->toggle(
            $product,
            (bool) $validated['is_new_arrival']
        );

        return response()->json([
            'success' => true,
            'message' => 'New Arrival status updated successfully.',
            'data' => $updated,
        ]);
    }
}
