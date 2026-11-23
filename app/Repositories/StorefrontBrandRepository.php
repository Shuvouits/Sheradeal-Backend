<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StorefrontBrandRepository
{
    public function findBySlug(string $slug): ?object
    {
        $query = DB::table('brands')
            ->where('slug', $slug);

        if (Schema::hasColumn('brands', 'status')) {
            $query->where('status', 'active');
        } elseif (Schema::hasColumn('brands', 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn('brands', 'publish_online')) {
            $query->where('publish_online', true);
        }

        return $query->first();
    }

    public function products(
        int $brandId,
        array $filters = []
    ): LengthAwarePaginator {
        $sort = $filters['sort'] ?? 'newest';

        $perPage = min(
            48,
            max(1, (int) ($filters['per_page'] ?? 20))
        );

        $query = Product::query()
            ->with('media')
            ->where('brand_id', $brandId)
            ->where('status', 'active')
            ->where('publish_online', true);

        if (Schema::hasColumn('products', 'published_at')) {
            $query->where(function ($builder) {
                $builder
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
        }

        // Product Table-এ selling_price থাকলে সেটি ব্যবহার করব।
        // না থাকলে price দিয়ে Sort হবে।
        $priceColumn = Schema::hasColumn(
            'products',
            'selling_price'
        )
            ? 'selling_price'
            : 'price';

        switch ($sort) {
            case 'price_asc':
                $query
                    ->orderBy($priceColumn, 'asc')
                    ->orderBy('id', 'desc');
                break;

            case 'price_desc':
                $query
                    ->orderBy($priceColumn, 'desc')
                    ->orderBy('id', 'desc');
                break;

            case 'name_asc':
                $query
                    ->orderBy('title', 'asc')
                    ->orderBy('id', 'desc');
                break;

            case 'name_desc':
                $query
                    ->orderBy('title', 'desc')
                    ->orderBy('id', 'desc');
                break;

            case 'oldest':
                $query->orderBy('id', 'asc');
                break;

            default:
                $query->orderBy('id', 'desc');
                break;
        }

        return $query->paginate($perPage);
    }
}
