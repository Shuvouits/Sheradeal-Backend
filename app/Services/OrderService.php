<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private OrderRepository $repository
    ) {}

    public function create(
        array $data,
        ?int $userId = null,
        string $source = 'website'
    ): Order {
        return DB::transaction(function () use ($data, $userId, $source) {
            $items = [];
            $subtotal = 0;
            $duplicates = [];

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];

                $variantId = isset($item['variant_id'])
                    ? (int) $item['variant_id']
                    : null;

                $key = $productId . ':' . ($variantId ?? 'base');

                if (isset($duplicates[$key])) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Duplicate cart items are not allowed.',
                        ],
                    ]);
                }

                $duplicates[$key] = true;
            }

            foreach ($data['items'] as $item) {
                $product = $this->repository->product(
                    (int) $item['product_id']
                );

                $productName = $this->resolveProductName($product);

                if ($productName === '') {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Product #{$product->id} has no valid name. Please update the product before placing an order.",
                        ],
                    ]);
                }

                if ($product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'items' => [
                            "{$productName} is not available.",
                        ],
                    ]);
                }

                $quantity = (int) $item['quantity'];

                if ($quantity < 1 || $quantity > 100) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Invalid quantity for {$productName}.",
                        ],
                    ]);
                }

                $variantId = $item['variant_id'] ?? null;
                $variant = null;

                if ($variantId !== null) {
                    $variant = DB::table('product_variants')
                        ->where('id', $variantId)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$variant) {
                        throw ValidationException::withMessages([
                            'items' => [
                                "Selected variant for {$productName} was not found.",
                            ],
                        ]);
                    }
                }

                $price = $this->resolvePrice($product, $variant);

                if ($price <= 0) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "{$productName} does not have a valid selling price.",
                        ],
                    ]);
                }

                $stock = $variant
                    ? ($variant->quantity ?? null)
                    : ($product->quantity ?? null);

                if ($stock === null || !is_numeric($stock)) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Stock is not configured for {$productName}.",
                        ],
                    ]);
                }

                if ((int) $stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Insufficient stock for {$productName}.",
                        ],
                    ]);
                }

                if ($variant) {
                    DB::table('product_variants')
                        ->where('id', $variant->id)
                        ->decrement('quantity', $quantity);
                } else {
                    $product->decrement('quantity', $quantity);
                }

                $lineTotal = round($price * $quantity, 2);
                $subtotal += $lineTotal;

                $productSku = $variant->sku
                    ?? $product->sku
                    ?? null;

                $items[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $productName,
                    'product_sku' => $productSku,
                    'product_image' => null,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ];
            }

            $deliveryCharge =
                $data['shipping_location'] === 'inside_dhaka'
                    ? 80
                    : 150;

            $subtotal = round($subtotal, 2);

            $calculatedTotal = round(
                $subtotal + $deliveryCharge,
                2
            );

            if (
                $source === 'website' &&
                array_key_exists('expected_total', $data)
            ) {
                $expectedTotal = round(
                    (float) $data['expected_total'],
                    2
                );

                if (abs($calculatedTotal - $expectedTotal) > 0.009) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Product prices have changed. Please refresh checkout before placing the order.',
                        ],
                    ]);
                }
            }

            $order = $this->repository->create([
                'order_number' => 'TMP-' . Str::uuid(),
                'user_id' => $userId,
                'source' => $source,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'shipping_location' => $data['shipping_location'],
                'customer_note' => $data['customer_note'] ?? null,
                'status' => 'pending',
                'payment_method' => 'cod',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'delivery_charge' => $deliveryCharge,
                'grand_total' => $calculatedTotal,
            ]);

            $order->update([
                'order_number' => sprintf(
                    'SHD-%06d',
                    $order->id
                ),
            ]);

            foreach ($items as $item) {
                $this->repository->addItem($order, $item);
            }

            $this->repository->addTimeline(
                $order,
                'created',
                'COD order placed successfully.',
                $userId,
                ['source' => $source]
            );

            return $this->repository->find($order->id);
        }, 3);
    }

    private function resolveProductName($product): string
    {
        $attributes = $product->getAttributes();

        $candidates = [
            $attributes['name'] ?? null,
            $attributes['title'] ?? null,
            $attributes['product_name'] ?? null,
            $product->name,
            $product->title,
            $product->product_name,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) && !is_numeric($candidate)) {
                continue;
            }

            $name = trim((string) $candidate);

            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }

    private function resolvePrice($product, $variant): float
    {
        $base = $variant?->price
            ?? $product->price
            ?? 0;

        $base = (float) $base;

        if ($variant) {
            return round($base, 2);
        }

        $special = $product->special_price ?? null;

        if (
            $special !== null &&
            is_numeric($special) &&
            (float) $special > 0 &&
            (float) $special < $base
        ) {
            return round((float) $special, 2);
        }

        return round($base, 2);
    }

    public function list(array $filters)
    {
        return $this->repository->paginate($filters);
    }

    public function statistics(): array
    {
        return $this->repository->statistics();
    }

    public function find(int $id): Order
    {
        return $this->repository->find($id);
    }

    public function customerOrders(int $userId)
    {
        return $this->repository->customerOrders($userId);
    }

    public function updateStatus(
        int $id,
        string $status,
        int $adminId
    ): Order {
        return DB::transaction(function () use ($id, $status, $adminId) {
            $order = $this->repository->locked($id);

            $transitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['processing', 'cancelled'],
                'processing' => ['shipped', 'cancelled'],
                'shipped' => ['delivered'],
                'delivered' => [],
                'cancelled' => [],
            ];

            $allowed = $transitions[$order->status] ?? [];

            if (!in_array($status, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Cannot change {$order->status} to {$status}.",
                    ],
                ]);
            }

            $oldStatus = $order->status;
            $updates = ['status' => $status];

            $timestamp = [
                'confirmed' => 'confirmed_at',
                'shipped' => 'shipped_at',
                'delivered' => 'delivered_at',
                'cancelled' => 'cancelled_at',
            ][$status] ?? null;

            if ($timestamp) {
                $updates[$timestamp] = now();
            }

            if ($status === 'cancelled') {
                $this->restoreStock($order);
            }

            $order->update($updates);

            $this->repository->addTimeline(
                $order,
                'status_changed',
                "Status changed from {$oldStatus} to {$status}.",
                $adminId,
                [
                    'from' => $oldStatus,
                    'to' => $status,
                ]
            );

            return $this->repository->find($id);
        }, 3);
    }

    private function restoreStock(Order $order): void
    {
        foreach (
            $order->items()
                ->orderBy('product_id')
                ->get() as $item
        ) {
            if ($item->product_variant_id) {
                DB::table('product_variants')
                    ->where('id', $item->product_variant_id)
                    ->increment('quantity', $item->quantity);
            } else {
                DB::table('products')
                    ->where('id', $item->product_id)
                    ->increment('quantity', $item->quantity);
            }
        }
    }

    public function confirmPayment(
        int $id,
        int $adminId
    ): Order {
        return DB::transaction(function () use ($id, $adminId) {
            $order = $this->repository->locked($id);

            if ($order->status !== 'delivered') {
                throw ValidationException::withMessages([
                    'payment' => [
                        'COD payment can only be confirmed after delivery.',
                    ],
                ]);
            }

            if ($order->payment_status === 'paid') {
                return $this->repository->find($id);
            }

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->repository->addTimeline(
                $order,
                'payment_received',
                'Cash on Delivery payment received.',
                $adminId,
                [
                    'amount' => $order->grand_total,
                    'method' => 'cod',
                ]
            );

            return $this->repository->find($id);
        }, 3);
    }

    public function addNote(
        int $id,
        int $adminId,
        string $message
    ): Order {
        return DB::transaction(function () use ($id, $adminId, $message) {
            $order = $this->repository->locked($id);

            $this->repository->addTimeline(
                $order,
                'admin_note',
                $message,
                $adminId
            );

            return $this->repository->find($id);
        });
    }
}
