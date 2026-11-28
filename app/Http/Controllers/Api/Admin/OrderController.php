<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'confirmed',
                    'processing',
                    'shipped',
                    'delivered',
                    'cancelled',
                ]),
            ],
            'payment_status' => [
                'nullable',
                Rule::in(['unpaid', 'paid']),
            ],
            'source' => [
                'nullable',
                Rule::in(['website', 'admin']),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->service->list($filters),
        ]);
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->statistics(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->find($id),
        ]);
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        $order = $this->service->create(
            $request->validated(),
            null,
            'admin'
        );

        $this->service->addNote(
            $order->id,
            (int) $request->user('api')->id,
            'Order created manually by administrator.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $this->service->find($order->id),
        ], 201);
    }

    public function updateStatus(
        Request $request,
        int $id
    ): JsonResponse {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'confirmed',
                    'processing',
                    'shipped',
                    'delivered',
                    'cancelled',
                ]),
            ],
        ]);

        $order = $this->service->updateStatus(
            $id,
            $data['status'],
            (int) $request->user('api')->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order,
        ]);
    }

    public function confirmPayment(
        Request $request,
        int $id
    ): JsonResponse {
        $order = $this->service->confirmPayment(
            $id,
            (int) $request->user('api')->id
        );

        return response()->json([
            'success' => true,
            'message' => 'COD payment confirmed successfully.',
            'data' => $order,
        ]);
    }

    public function addNote(
        Request $request,
        int $id
    ): JsonResponse {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $order = $this->service->addNote(
            $id,
            (int) $request->user('api')->id,
            $data['message']
        );

        return response()->json([
            'success' => true,
            'message' => 'Order note added successfully.',
            'data' => $order,
        ], 201);
    }
}
