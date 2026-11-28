<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class OrderWorkflowRepository
{
    public function list(string $table, array $filters = [])
    {
        $query = DB::table($table);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';

            $columns = match ($table) {
                'preorders' => [
                    'preorder_number',
                    'customer_name',
                    'customer_phone',
                ],
                'order_quotes' => [
                    'quote_number',
                    'customer_name',
                    'customer_phone',
                ],
                'order_returns' => ['return_number'],
                'abandoned_checkouts' => [
                    'customer_name',
                    'customer_phone',
                ],
                default => [],
            };

            if ($columns) {
                $query->where(function ($q) use ($columns, $search) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'like', $search);
                    }
                });
            }
        }

        return $query->orderByDesc('id')
            ->paginate(
                min(max((int) ($filters['per_page'] ?? 20), 1), 100)
            );
    }

    public function find(string $table, int $id)
    {
        return DB::table($table)->where('id', $id)->first()
            ?? abort(404, 'Record not found.');
    }

    public function locked(string $table, int $id)
    {
        return DB::table($table)
            ->where('id', $id)
            ->lockForUpdate()
            ->first()
            ?? abort(404, 'Record not found.');
    }

    public function insert(string $table, array $data): int
    {
        return DB::table($table)->insertGetId([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function update(string $table, int $id, array $data): void
    {
        DB::table($table)->where('id', $id)->update([
            ...$data,
            'updated_at' => now(),
        ]);
    }

    public function items(string $table, string $foreignKey, int $id)
    {
        return DB::table($table)
            ->where($foreignKey, $id)
            ->get();
    }
}
