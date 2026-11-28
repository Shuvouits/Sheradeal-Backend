<?php

namespace App\Services;

use App\Repositories\OrderWorkflowRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function __construct(
        private OrderWorkflowRepository $repository,
        private OrderService $orderService
    ) {}

    private function delivery(string $location): float
    {
        return $location === 'inside_dhaka' ? 80 : 150;
    }

    private function productItems(array $items): array
    {
        $result = [];
        $subtotal = 0;
        $seen = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $variantId = isset($item['variant_id'])
                ? (int) $item['variant_id']
                : null;
            $quantity = (int) $item['quantity'];
            $key = "{$productId}:" . ($variantId ?? 'base');

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'items' => ['Duplicate products are not allowed.'],
                ]);
            }

            $seen[$key] = true;

            $product = DB::table('products')
                ->where('id', $productId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();

            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => ["Product {$productId} is unavailable."],
                ]);
            }

            $variant = null;

            if ($variantId !== null) {
                $variant = DB::table('product_variants')
                    ->where('id', $variantId)
                    ->where('product_id', $productId)
                    ->first();

                if (!$variant) {
                    throw ValidationException::withMessages([
                        'items' => ['Invalid product variant selected.'],
                    ]);
                }
            }

            $basePrice = (float) (
                $variant->price ?? $product->price ?? 0
            );

            $special = $variant
                ? null
                : ($product->special_price ?? null);

            $price = $special !== null &&
                (float) $special > 0 &&
                (float) $special < $basePrice
                    ? (float) $special
                    : $basePrice;

            if ($price <= 0) {
                throw ValidationException::withMessages([
                    'items' => ["Invalid price for {$product->name}."],
                ]);
            }

            $lineTotal = round($price * $quantity, 2);
            $subtotal += $lineTotal;

            $result[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => round($price, 2),
                'line_total' => $lineTotal,
            ];
        }

        return [
            'items' => $result,
            'subtotal' => round($subtotal, 2),
        ];
    }

    private function saveItems(
        string $table,
        string $foreignKey,
        int $id,
        array $items
    ): void {
        foreach ($items as $item) {
            $this->repository->insert($table, [
                $foreignKey => $id,
                ...$item,
            ]);
        }
    }

    private function orderPayload($record, $items): array
    {
        return [
            'customer_name' => $record->customer_name,
            'customer_phone' => $record->customer_phone,
            'customer_email' => $record->customer_email,
            'delivery_address' => $record->delivery_address,
            'shipping_location' => $record->shipping_location,
            'customer_note' => $record->note,
            'payment_method' => 'cod',
            'items' => $items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
            ])->all(),
        ];
    }

    public function createPreorder(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $pricing = $this->productItems($data['items']);
            $delivery = $this->delivery($data['shipping_location']);

            $id = $this->repository->insert('preorders', [
                'preorder_number' => 'TMP-' . (string) \Illuminate\Support\Str::uuid(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'shipping_location' => $data['shipping_location'],
                'note' => $data['note'] ?? null,
                'expected_date' => $data['expected_date'],
                'status' => 'pending',
                'subtotal' => $pricing['subtotal'],
                'delivery_charge' => $delivery,
                'total' => $pricing['subtotal'] + $delivery,
            ]);

            $this->repository->update('preorders', $id, [
                'preorder_number' => sprintf('PRE-%06d', $id),
            ]);

            $this->saveItems(
                'preorder_items',
                'preorder_id',
                $id,
                $pricing['items']
            );

            return $this->preorder($id);
        });
    }

    public function preorder(int $id): array
    {
        return [
            'record' => $this->repository->find('preorders', $id),
            'items' => $this->repository->items(
                'preorder_items',
                'preorder_id',
                $id
            ),
        ];
    }

    public function releasePreorder(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $record = $this->repository->locked('preorders', $id);

            if ($record->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Only pending pre-orders can be released.'],
                ]);
            }

            $items = $this->repository->items(
                'preorder_items',
                'preorder_id',
                $id
            );

            $order = $this->orderService->create(
                $this->orderPayload($record, $items),
                null,
                'admin'
            );

            $this->repository->update('preorders', $id, [
                'status' => 'released',
                'order_id' => $order->id,
            ]);

            return $this->preorder($id);
        }, 3);
    }

    public function cancelPreorder(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $record = $this->repository->locked('preorders', $id);

            if ($record->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Only pending pre-orders can be cancelled.'],
                ]);
            }

            $this->repository->update('preorders', $id, [
                'status' => 'cancelled',
            ]);

            return $this->preorder($id);
        });
    }

    public function createQuote(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $pricing = $this->productItems($data['items']);
            $delivery = $this->delivery($data['shipping_location']);

            $id = $this->repository->insert('order_quotes', [
                'quote_number' => 'TMP-' . (string) \Illuminate\Support\Str::uuid(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'shipping_location' => $data['shipping_location'],
                'note' => $data['note'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'subtotal' => $pricing['subtotal'],
                'delivery_charge' => $delivery,
                'total' => $pricing['subtotal'] + $delivery,
                'status' => 'draft',
            ]);

            $this->repository->update('order_quotes', $id, [
                'quote_number' => sprintf('QUO-%06d', $id),
            ]);

            $this->saveItems(
                'quote_items',
                'quote_id',
                $id,
                $pricing['items']
            );

            return $this->quote($id);
        });
    }

    public function quote(int $id): array
    {
        return [
            'record' => $this->repository->find('order_quotes', $id),
            'items' => $this->repository->items(
                'quote_items',
                'quote_id',
                $id
            ),
        ];
    }

    public function updateQuoteStatus(int $id, string $status): array
    {
        return DB::transaction(function () use ($id, $status) {
            $quote = $this->repository->locked('order_quotes', $id);

            $allowed = [
                'draft' => ['sent', 'cancelled'],
                'sent' => ['accepted', 'rejected', 'cancelled'],
                'accepted' => [],
                'rejected' => [],
                'cancelled' => [],
                'converted' => [],
            ];

            if (!in_array($status, $allowed[$quote->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Invalid quotation status transition.'],
                ]);
            }

            if (
                $status === 'accepted' &&
                $quote->expires_at &&
                now()->greaterThan($quote->expires_at)
            ) {
                throw ValidationException::withMessages([
                    'status' => ['This quotation has expired.'],
                ]);
            }

            $this->repository->update('order_quotes', $id, [
                'status' => $status,
            ]);

            return $this->quote($id);
        });
    }

    public function convertQuote(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $quote = $this->repository->locked('order_quotes', $id);

            if ($quote->status !== 'accepted') {
                throw ValidationException::withMessages([
                    'status' => ['Only accepted quotes can become orders.'],
                ]);
            }

            if (
                $quote->expires_at &&
                now()->greaterThan($quote->expires_at)
            ) {
                throw ValidationException::withMessages([
                    'status' => ['This quotation has expired.'],
                ]);
            }

            $items = $this->repository->items(
                'quote_items',
                'quote_id',
                $id
            );

            $order = $this->orderService->create(
                $this->orderPayload($quote, $items),
                null,
                'admin'
            );

            $this->repository->update('order_quotes', $id, [
                'status' => 'converted',
                'order_id' => $order->id,
            ]);

            return $this->quote($id);
        }, 3);
    }

    public function createReturn(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $order = DB::table('orders')
                ->where('id', $data['order_id'])
                ->lockForUpdate()
                ->first();

            if (
                !$order ||
                $order->status !== 'delivered' ||
                $order->payment_status !== 'paid'
            ) {
                throw ValidationException::withMessages([
                    'order_id' => [
                        'Returns require a delivered and paid COD order.',
                    ],
                ]);
            }

            $item = DB::table('order_items')
                ->where('id', $data['order_item_id'])
                ->where('order_id', $order->id)
                ->first();

            if (!$item) {
                throw ValidationException::withMessages([
                    'order_item_id' => ['Invalid order item.'],
                ]);
            }

            $alreadyReturned = DB::table('order_returns')
                ->where('order_item_id', $item->id)
                ->where('status', '!=', 'rejected')
                ->sum('quantity');

            if (
                $alreadyReturned + $data['quantity'] >
                $item->quantity
            ) {
                throw ValidationException::withMessages([
                    'quantity' => ['Return quantity exceeds purchased quantity.'],
                ]);
            }

            $refund = round(
                (float) $item->unit_price * (int) $data['quantity'],
                2
            );

            $id = $this->repository->insert('order_returns', [
                'return_number' => 'TMP-' . (string) \Illuminate\Support\Str::uuid(),
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'quantity' => $data['quantity'],
                'reason' => $data['reason'],
                'note' => $data['note'] ?? null,
                'status' => 'requested',
                'refund_method' => 'cash',
                'refund_amount' => $refund,
            ]);

            $this->repository->update('order_returns', $id, [
                'return_number' => sprintf('RET-%06d', $id),
            ]);

            return $this->repository->find('order_returns', $id);
        });
    }

    public function updateReturn(
        int $id,
        string $status,
        int $adminId
    ): object {
        return DB::transaction(function () use ($id, $status, $adminId) {
            $return = $this->repository->locked('order_returns', $id);

            $allowed = [
                'requested' => ['approved', 'rejected'],
                'approved' => ['received'],
                'received' => ['refunded'],
                'rejected' => [],
                'refunded' => [],
            ];

            if (!in_array($status, $allowed[$return->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Invalid return status transition.'],
                ]);
            }

            $updates = ['status' => $status];

            if ($status === 'received') {
                $item = DB::table('order_items')
                    ->where('id', $return->order_item_id)
                    ->first();

                if ($item->product_variant_id) {
                    $updated = DB::table('product_variants')
                        ->where('id', $item->product_variant_id)
                        ->increment('quantity', $return->quantity);
                } else {
                    $updated = DB::table('products')
                        ->where('id', $item->product_id)
                        ->increment('quantity', $return->quantity);
                }

                if (!$updated) {
                    throw ValidationException::withMessages([
                        'stock' => ['Unable to restock returned product.'],
                    ]);
                }

                $updates['received_at'] = now();
            }

            if ($status === 'refunded') {
                $order = DB::table('orders')
                    ->where('id', $return->order_id)
                    ->lockForUpdate()
                    ->first();

                if ($order->payment_status !== 'paid') {
                    throw ValidationException::withMessages([
                        'payment' => ['Order payment has not been collected.'],
                    ]);
                }

                $updates['refunded_at'] = now();
            }

            $this->repository->update('order_returns', $id, $updates);

            DB::table('order_timelines')->insert([
                'order_id' => $return->order_id,
                'user_id' => $adminId,
                'event' => 'return_' . $status,
                'message' => "Return {$return->return_number} marked {$status}.",
                'metadata' => json_encode([
                    'return_id' => $id,
                    'refund_amount' => $return->refund_amount,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->repository->find('order_returns', $id);
        }, 3);
    }

    public function list(string $type, array $filters = [])
    {
        $tables = [
            'preorders' => 'preorders',
            'returns' => 'order_returns',
            'quotes' => 'order_quotes',
            'abandoned' => 'abandoned_checkouts',
        ];

        abort_unless(isset($tables[$type]), 404);

        return $this->repository->list($tables[$type], $filters);
    }

    public function abandonedDetail(int $id): object
    {
        return $this->repository->find('abandoned_checkouts', $id);
    }

    public function archiveAbandoned(int $id): object
    {
        return DB::transaction(function () use ($id) {
            $record = $this->repository->locked('abandoned_checkouts', $id);

            if ($record->status === 'open') {
                $this->repository->update('abandoned_checkouts', $id, [
                    'status' => 'archived',
                ]);
            }

            return $this->repository->find('abandoned_checkouts', $id);
        });
    }
}
