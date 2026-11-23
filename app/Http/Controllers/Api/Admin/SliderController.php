<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SliderRequest;
use App\Services\SliderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    public function __construct(
        private SliderService $service
    ) {}

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user('api');

        abort_unless(
            $user && in_array(
                $user->role,
                ['admin', 'super_admin'],
                true
            ),
            403,
            'Administrator access required.'
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'success' => true,
            'data' => $this->service->list(
                min(max((int) $request->input('per_page', 15), 1), 100)
            ),
        ]);
    }

    public function store(SliderRequest $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $slider = $this->service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Slider created successfully.',
            'data' => $slider,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'success' => true,
            'data' => $this->service->find($id),
        ]);
    }

    public function update(SliderRequest $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $slider = $this->service->update(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Slider updated successfully.',
            'data' => $slider,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Slider deleted successfully.',
        ]);
    }

    public function storefront(SliderService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->visible(),
        ]);
    }
}
