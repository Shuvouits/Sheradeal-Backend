<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\StoreOutletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreOutletController extends Controller
{
    public function __construct(
        private StoreOutletService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->all(),
        ]);
    }

    public function show(int $outlet): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->show($outlet),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateOutlet($request);

        return response()->json([
            'success' => true,
            'message' => 'Outlet created successfully.',
            'data' => $this->service->create($data),
        ], 201);
    }

    public function update(
        Request $request,
        int $outlet
    ): JsonResponse {
        $data = $this->validateOutlet($request);

        return response()->json([
            'success' => true,
            'message' => 'Outlet updated successfully.',
            'data' => $this->service->update($outlet, $data),
        ]);
    }

    public function destroy(int $outlet): JsonResponse
    {
        $this->service->delete($outlet);

        return response()->json([
            'success' => true,
            'message' => 'Outlet deleted successfully.',
        ]);
    }

    public function uploadImages(
        Request $request,
        int $outlet
    ): JsonResponse {
        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:12'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully.',
            'data' => $this->service->addImages(
                $outlet,
                $data['images']
            ),
        ]);
    }

    public function deleteImage(
        int $outlet,
        int $image
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
            'data' => $this->service->removeImage(
                $outlet,
                $image
            ),
        ]);
    }

    private function validateOutlet(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'address' => ['required', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'map_query' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'array', 'max:14'],
            'opening_hours.*.days' => [
                'required',
                'string',
                'max:80',
            ],
            'opening_hours.*.hours' => [
                'required',
                'string',
                'max:100',
            ],
            'display_order' => [
                'required',
                'integer',
                'min:0',
            ],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
