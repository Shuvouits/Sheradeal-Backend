<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class StorefrontProductSearchRepository
{
    public function search(array $filters): LengthAwarePaginator
    {
        $keyword = trim((string) ($filters['search'] ?? ''));
        $sort = $filters['sort'] ?? 'relevance';

        $perPage = min(
            48,
            max(1, (int) ($filters['per_page'] ?? 20))
        );

        $query = Product::query()
            ->with('media')
            ->where('products.status', 'active')
            ->where('products.publish_online', true);

        if (Schema::hasColumn('products', 'published_at')) {
            $query->where(function ($builder) {
                $builder
                    ->whereNull('products.published_at')
                    ->orWhere('products.published_at', '<=', now());
            });
        }

        if ($keyword !== '') {
            $term = '%' . addcslashes($keyword, '%_\\') . '%';

            $query->where(function ($builder) use ($term) {
                $builder->where('products.title', 'like', $term);

                if (Schema::hasColumn('products', 'sku')) {
                    $builder->orWhere('products.sku', 'like', $term);
                }

                if (Schema::hasColumn('products', 'description')) {
                    $builder->orWhere(
                        'products.description',
                        'like',
                        $term
                    );
                }
            });
        }

        switch ($sort) {
            case 'price_asc':
                $query
                    ->orderBy('products.price', 'asc')
                    ->orderByDesc('products.id');
                break;

            case 'price_desc':
                $query
                    ->orderBy('products.price', 'desc')
                    ->orderByDesc('products.id');
                break;

            case 'name_asc':
                $query
                    ->orderBy('products.title', 'asc')
                    ->orderByDesc('products.id');
                break;

            case 'newest':
                $query->orderByDesc('products.id');
                break;

            default:
                if ($keyword !== '') {
                    $query->orderByRaw(
                        "CASE
                            WHEN LOWER(products.title) = LOWER(?) THEN 0
                            WHEN LOWER(products.title) LIKE LOWER(?) THEN 1
                            ELSE 2
                        END",
                        [
                            $keyword,
                            addcslashes($keyword, '%_\\') . '%',
                        ]
                    );
                }

                $query->orderByDesc('products.id');
                break;
        }

        return $query->paginate($perPage);
    }
}
