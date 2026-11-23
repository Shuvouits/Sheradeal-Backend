<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdvertisementRequest;
use App\Services\AdvertisementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function __construct(
        private AdvertisementService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Advertisements retrieved successfully.',
            'data' => $this->service->list($perPage),
        ]);
    }

    public function store(AdvertisementRequest $request): JsonResponse
    {
        $advertisement = $this->service->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created successfully.',
            'data' => $advertisement,
        ], 201);
    }

    public function show(int $advertisement): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->find($advertisement),
        ]);
    }

    public function update(
        AdvertisementRequest $request,
        int $advertisement
    ): JsonResponse {
        $updated = $this->service->update(
            $advertisement,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated successfully.',
            'data' => $updated,
        ]);
    }

    public function destroy(int $advertisement): JsonResponse
    {
        $this->service->delete($advertisement);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully.',
        ]);
    }
}
