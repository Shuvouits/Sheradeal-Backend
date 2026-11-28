<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function find(int $id): Order
    {
        return Order::with(['items', 'timeline'])
            ->findOrFail($id);
    }

    public function locked(int $id): Order
    {
        return Order::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function product(int $id): Product
    {
        return Product::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function addItem(Order $order, array $data): void
    {
        $order->items()->create($data);
    }

    public function addTimeline(
        Order $order,
        string $event,
        string $message,
        ?int $userId = null,
        array $metadata = []
    ): void {
        $order->timeline()->create([
            'user_id' => $userId,
            'event' => $event,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }

    public function paginate(array $filters)
    {
        $query = Order::query()->withCount('items');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where(
                'payment_status',
                $filters['payment_status']
            );
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        return $query
            ->latest('id')
            ->paginate(
                min(max((int) ($filters['per_page'] ?? 20), 1), 100)
            );
    }

    public function customerOrders(int $userId)
    {
        return Order::query()
            ->where('user_id', $userId)
            ->with('items')
            ->latest('id')
            ->paginate(15);
    }

    public function statistics(): array
    {
        $counts = Order::query()
            ->selectRaw('COUNT(*) AS total_orders')
            ->selectRaw(
                "SUM(CASE WHEN status IN ('pending','confirmed','processing','shipped') THEN 1 ELSE 0 END) AS open_orders"
            )
            ->selectRaw(
                "SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_orders"
            )
            ->selectRaw(
                "SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) AS total_revenue"
            )
            ->first();

        $paidOrders = (int) ($counts->paid_orders ?? 0);
        $revenue = (float) ($counts->total_revenue ?? 0);

        return [
            'total_orders' => (int) ($counts->total_orders ?? 0),
            'open_orders' => (int) ($counts->open_orders ?? 0),
            'paid_orders' => $paidOrders,
            'total_revenue' => round($revenue, 2),
            'average_order_value' => $paidOrders > 0
                ? round($revenue / $paidOrders, 2)
                : 0,
            'currency' => 'BDT',
        ];
    }
}
