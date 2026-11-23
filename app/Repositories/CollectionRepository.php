<?php

namespace App\Repositories;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class CollectionRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Collection::query()
            ->withCount('products');

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['collection_type'])) {
            $query->where(
                'collection_type',
                $filters['collection_type']
            );
        }

        return $query
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findOrFail(int $id): Collection
    {
        return Collection::query()->findOrFail($id);
    }

    public function findPublishedBySlug(string $slug): Collection
    {
        return Collection::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function slugExists(
        string $slug,
        ?int $ignoreId = null
    ): bool {
        return Collection::query()
            ->where('slug', $slug)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->exists();
    }

    public function create(array $data): Collection
    {
        return Collection::create($data);
    }

    public function update(
        Collection $collection,
        array $data
    ): Collection {
        $collection->update($data);

        return $collection->refresh();
    }

    public function delete(Collection $collection): void
    {
        $collection->delete();
    }

    public function syncProducts(
        Collection $collection,
        array $productIds
    ): void {
        $sync = [];

        foreach (array_values($productIds) as $index => $id) {
            $sync[(int) $id] = [
                'display_order' => $index,
            ];
        }

        $collection->products()->sync($sync);
    }

    public function selectedProducts(
        Collection $collection
    ): EloquentCollection {
        return $collection->products()
            ->orderByPivot('display_order')
            ->get();
    }

    public function validProductCount(array $productIds): int
    {
        return Product::query()
            ->whereIn('id', $productIds)
            ->count();
    }

    public function searchProducts(
        array $filters
    ): LengthAwarePaginator {
        return Product::query()
            ->when(
                !empty($filters['search']),
                function (Builder $query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function (Builder $query) use ($search) {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                !empty($filters['category_id']),
                fn (Builder $query) => $query->where(
                    'category_id',
                    $filters['category_id']
                )
            )
            ->orderBy('title')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function productQuery(Collection $collection): Builder
    {
        $query = Product::query()
            ->with('media')
            ->where('products.status', 'active')
            ->where('products.publish_online', true)
            ->where(function (Builder $query) {
                $query->whereNull('products.published_at')
                    ->orWhere('products.published_at', '<=', now());
            });

        if ($collection->collection_type === 'manual') {
            $query->join(
                'collection_product as cp',
                'cp.product_id',
                '=',
                'products.id'
            )
                ->where('cp.collection_id', $collection->id)
                ->select('products.*');

            $this->applySorting($query, $collection, true);

            return $query;
        }

        if ($collection->rule_category_id) {
            $query->where(
                'products.category_id',
                $collection->rule_category_id
            );
        }

        if ($collection->rule_brand_id) {
            $query->where(
                'products.brand_id',
                $collection->rule_brand_id
            );
        }

        if ($collection->rule_min_price !== null) {
            $query->where(
                'products.price',
                '>=',
                $collection->rule_min_price
            );
        }

        if ($collection->rule_max_price !== null) {
            $query->where(
                'products.price',
                '<=',
                $collection->rule_max_price
            );
        }

        $this->applySorting($query, $collection);

        return $query;
    }

    public function storefrontProducts(
        Collection $collection,
        int $limit = 24
    ): EloquentCollection {
        return $this->productQuery($collection)
            ->limit($limit)
            ->get();
    }

    public function storefrontProductCount(
        Collection $collection
    ): int {
        return $this->productQuery($collection)->count();
    }

    public function publishedCollections(): EloquentCollection
    {
        return Collection::query()
            ->published()
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->get();
    }

    public function statistics(): array
    {
        return [
            'total_collections' => Collection::count(),

            'active_collections' => Collection::query()
                ->where('status', 'active')
                ->count(),

            'draft_collections' => Collection::query()
                ->where('status', 'draft')
                ->count(),

            'manual_collections' => Collection::query()
                ->where('collection_type', 'manual')
                ->count(),

            'automated_collections' => Collection::query()
                ->where('collection_type', 'automated')
                ->count(),

            'online_collections' => Collection::query()
                ->where('status', 'active')
                ->where('publish_online', true)
                ->count(),

            'assigned_products' => \Illuminate\Support\Facades\DB::table(
                'collection_product'
            )->count(),
        ];
    }

    private function applySorting(
        Builder $query,
        Collection $collection,
        bool $hasPivot = false
    ): void {
        switch ($collection->sort_by) {
            case 'price_asc':
                $query->orderBy('products.price');
                break;

            case 'price_desc':
                $query->orderByDesc('products.price');
                break;

            case 'title_asc':
                $query->orderBy('products.title');
                break;

            case 'manual':
                if ($hasPivot) {
                    $query->orderBy('cp.display_order');
                    break;
                }

                // Automated collections have no manual pivot order.
                $query->orderByDesc('products.created_at');
                break;

            case 'newest':
            default:
                $query->orderByDesc('products.created_at');
                break;
        }

        $query->orderByDesc('products.id');
    }
}
