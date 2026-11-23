<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class PopularProductRepository
{
    public function findOrFail(int $id): Product
    {
        return Product::query()->findOrFail($id);
    }

    public function updatePopularity(
        Product $product,
        bool $isPopular,
        int $sortOrder
    ): Product {
        $product->is_popular = $isPopular;
        $product->popular_sort_order = $sortOrder;
        $product->save();

        return $product->refresh();
    }

    public function getPopularProducts(int $limit = 12): Collection
    {
        return Product::query()
            ->with([
                'media' => function ($query) {
                    $query
                        ->orderByDesc('is_cover')
                        ->orderBy('display_order');
                },
            ])
            ->where('status', 'active')
            ->where('publish_online', true)
            ->where('is_popular', true)
            ->where(function ($query) {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderBy('popular_sort_order')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
