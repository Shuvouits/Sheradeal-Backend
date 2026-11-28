<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $service
    ) {}

    public function store(CheckoutRequest $request): JsonResponse
    {
        $order = $this->service->create(
            $request->validated(),
            auth('api')->id(),
            'website'
        );

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data' => $order,
        ], 201);
    }

    public function myOrders(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->customerOrders(
                (int) $request->user('api')->id
            ),
        ]);
    }

    public function myOrder(
        Request $request,
        int $id
    ): JsonResponse {
        $order = $this->service->find($id);

        abort_unless(
            $order->user_id === (int) $request->user('api')->id,
            404,
            'Order not found.'
        );

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }
}
