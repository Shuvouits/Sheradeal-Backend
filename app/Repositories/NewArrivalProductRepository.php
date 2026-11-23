<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class NewArrivalProductRepository
{
    public function findOrFail(int $id): Product
    {
        return Product::query()->findOrFail($id);
    }

    public function updateStatus(
        Product $product,
        bool $isNewArrival
    ): Product {
        $product->is_new_arrival = $isNewArrival;
        $product->save();

        return $product->refresh();
    }

    public function getStorefrontProducts(int $limit = 15): Collection
    {
        return Product::query()
            ->with('media')
            ->where('is_new_arrival', true)
            ->where('status', 'active')
            ->where('publish_online', true)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
