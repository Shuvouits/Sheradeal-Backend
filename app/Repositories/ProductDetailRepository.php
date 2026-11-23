<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductDetailRepository
{
    private function availableProducts()
    {
        $query = Product::query()
            ->with('media')
            ->where('status', 'active')
            ->where('publish_online', true);

        if (Schema::hasColumn('products', 'published_at')) {
            $query->where(function ($builder) {
                $builder->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
        }

        return $query;
    }

    public function findBySlug(string $slug): Product
    {
        return $this->availableProducts()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function related(Product $product, int $limit = 12): Collection
    {
        $query = $this->availableProducts()
            ->whereKeyNot($product->id);

        if ($product->category_id) {
            $query->where('category_id', $product->category_id);
        } elseif ($product->brand_id) {
            $query->where('brand_id', $product->brand_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function sameBrand(Product $product, int $limit = 10): Collection
    {
        if (!$product->brand_id) {
            return new Collection();
        }

        return $this->availableProducts()
            ->where('brand_id', $product->brand_id)
            ->whereKeyNot($product->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function brand(int|string|null $id): ?array
    {
        if (!$id || !Schema::hasTable('brands')) {
            return null;
        }

        $record = DB::table('brands')->where('id', $id)->first();

        if (!$record) {
            return null;
        }

        return [
            'id' => $record->id,
            'name' => $record->name
                ?? $record->title
                ?? 'Brand',
        ];
    }

    public function category(int|string|null $id): ?array
    {
        if (!$id || !Schema::hasTable('categories')) {
            return null;
        }

        $record = DB::table('categories')->where('id', $id)->first();

        if (!$record) {
            return null;
        }

        return [
            'id' => $record->id,
            'name' => $record->name
                ?? $record->title
                ?? 'Category',
        ];
    }
}
