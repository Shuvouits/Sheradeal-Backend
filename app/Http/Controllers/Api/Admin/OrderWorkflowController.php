<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderWorkflowController extends Controller
{
    public function __construct(
        private OrderWorkflowService $service
    ) {}

    private function customerRules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'shipping_location' => [
                'required',
                Rule::in(['inside_dhaka', 'outside_dhaka']),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => [
                'required', 'integer', 'exists:products,id',
            ],
            'items.*.variant_id' => ['nullable', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    private function respond($data, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $code);
    }

    public function list(Request $request, string $type): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->respond(
            $this->service->list($type, $filters)
        );
    }

    public function createPreorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            ...$this->customerRules(),
            'expected_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        return $this->respond(
            $this->service->createPreorder($data),
            201
        );
    }

    public function preorder(int $id): JsonResponse
    {
        return $this->respond($this->service->preorder($id));
    }

    public function releasePreorder(int $id): JsonResponse
    {
        return $this->respond(
            $this->service->releasePreorder($id)
        );
    }

    public function cancelPreorder(int $id): JsonResponse
    {
        return $this->respond(
            $this->service->cancelPreorder($id)
        );
    }

    public function createReturn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'order_item_id' => [
                'required', 'integer', 'exists:order_items,id',
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'reason' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->respond(
            $this->service->createReturn($data),
            201
        );
    }

    public function updateReturn(
        Request $request,
        int $id
    ): JsonResponse {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'approved',
                    'rejected',
                    'received',
                    'refunded',
                ]),
            ],
        ]);

        return $this->respond(
            $this->service->updateReturn(
                $id,
                $data['status'],
                (int) $request->user('api')->id
            )
        );
    }

    public function createQuote(Request $request): JsonResponse
    {
        $data = $request->validate([
            ...$this->customerRules(),
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        return $this->respond(
            $this->service->createQuote($data),
            201
        );
    }

    public function quote(int $id): JsonResponse
    {
        return $this->respond($this->service->quote($id));
    }

    public function updateQuote(
        Request $request,
        int $id
    ): JsonResponse {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'sent',
                    'accepted',
                    'rejected',
                    'cancelled',
                ]),
            ],
        ]);

        return $this->respond(
            $this->service->updateQuoteStatus(
                $id,
                $data['status']
            )
        );
    }

    public function convertQuote(int $id): JsonResponse
    {
        return $this->respond(
            $this->service->convertQuote($id)
        );
    }

    public function abandoned(int $id): JsonResponse
    {
        return $this->respond(
            $this->service->abandonedDetail($id)
        );
    }

    public function archiveAbandoned(int $id): JsonResponse
    {
        return $this->respond(
            $this->service->archiveAbandoned($id)
        );
    }

    public function products(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $products = \App\Models\Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($data) {
                $search = '%' . $data['search'] . '%';

                $query->where('name', 'like', $search)
                    ->orWhere('sku', 'like', $search);
            })
            ->select([
                'id',
                'name',
                'sku',
                'price',
                'special_price',
                'quantity',
            ])
            ->limit(20)
            ->get();

        return $this->respond($products);
    }
}
