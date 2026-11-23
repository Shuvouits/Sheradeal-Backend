<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

    public function query(): Builder
    {
        return Product::query();
    }

    /*
    |--------------------------------------------------------------------------
    | Product Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(array $filters = []): LengthAwarePaginator
    {



$query = Product::query()
    ->with([
        'brand:id,name',
        'category:id,name',
        'media' => function ($query) {
            $query->where('is_cover', true)
                ->where('media_type', 'image')
                ->orderBy('display_order')
                ->orderBy('id');
        },
    ]);


        // Search
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($variantQuery) use ($search) {
                        $variantQuery
                            ->where('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
            });
        }

        // Status
        if (!empty($filters['status']) &&
            $filters['status'] !== 'all') {

            $query->where('status', $filters['status']);
        }

        // Category
        if (!empty($filters['category_id'])) {
            $query->where(
                'category_id',
                $filters['category_id']
            );
        }

        // Brand
        if (!empty($filters['brand_id'])) {
            $query->where(
                'brand_id',
                $filters['brand_id']
            );
        }

        // Featured
        if (isset($filters['is_featured'])) {
            $query->where(
                'is_featured',
                filter_var(
                    $filters['is_featured'],
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        // Publishing channel
        if (isset($filters['publish_online'])) {
            $query->where(
                'publish_online',
                filter_var(
                    $filters['publish_online'],
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        // Product Type
        if (!empty($filters['product_type'])) {
            $query->where(
                'product_type',
                $filters['product_type']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'title',
            'price',
            'quantity',
            'status',
            'created_at',
            'updated_at',
        ];

        $sortBy = $filters['sort_by'] ?? 'created_at';

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $sortDirection = strtolower(
            $filters['sort_direction'] ?? 'desc'
        );

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Statistics
    |--------------------------------------------------------------------------
    */

    public function statistics(): array
    {
        $stats = Product::query()
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw(
                "SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_products"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_products"
            )
            ->first();

        $simpleStock = Product::query()
            ->where('has_variants', false)
            ->sum('quantity');

        $variantStock = DB::table('product_variants')
            ->join(
                'products',
                'products.id',
                '=',
                'product_variants.product_id'
            )
            ->whereNull('products.deleted_at')
            ->whereNull('product_variants.deleted_at')
            ->where('products.has_variants', true)
            ->sum('product_variants.quantity');

        $simpleOutOfStock = Product::query()
            ->where('has_variants', false)
            ->where('track_quantity', true)
            ->where('quantity', 0)
            ->count();

        $variantOutOfStock = Product::query()
            ->where('has_variants', true)
            ->whereDoesntHave('variants', function ($query) {
                $query->where(function ($stock) {
                    $stock->where('track_quantity', false)
                        ->orWhere('continue_selling_out_of_stock', true)
                        ->orWhere('quantity', '>', 0);
                });
            })
            ->count();

        return [
            'total_products' => (int) ($stats->total_products ?? 0),
            'active_products' => (int) ($stats->active_products ?? 0),
            'draft_products' => (int) ($stats->draft_products ?? 0),
            'archived_products' => (int) ($stats->archived_products ?? 0),
            'out_of_stock' => $simpleOutOfStock + $variantOutOfStock,
            'inventory_units' => (int) ($simpleStock + $variantStock),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Find Product
    |--------------------------------------------------------------------------
    */

    public function find(int $id): Product
    {
        return Product::query()
            ->with([
                'brand',
                'category',
                'media',
                'variants.values.globalVariant',
                'variants.values.option',
            ])
            ->findOrFail($id);
    }

    public function findBySlug(string $slug): Product
    {
        return Product::query()
            ->with([
                'brand',
                'category',
                'media',
                'variants.values.globalVariant',
                'variants.values.option',
            ])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function findForUpdate(int $id): Product
    {
        return Product::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Product
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    */

    public function update(
        Product $product,
        array $data
    ): Product {
        $product->update($data);

        return $product->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Soft Delete
    |--------------------------------------------------------------------------
    */

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Product
    |--------------------------------------------------------------------------
    */

    public function restore(int $id): Product
    {
        $product = Product::onlyTrashed()->findOrFail($id);

        $product->restore();

        return $product->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Check Slug
    |--------------------------------------------------------------------------
    */

    public function slugExists(
        string $slug,
        ?int $excludeId = null
    ): bool {
        return Product::withTrashed()
            ->where('slug', $slug)
            ->when(
                $excludeId !== null,
                fn ($query) => $query->where('id', '!=', $excludeId)
            )
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Check SKU
    |--------------------------------------------------------------------------
    */

    public function skuExists(
        string $sku,
        ?int $excludeId = null
    ): bool {
        return Product::withTrashed()
            ->where('sku', $sku)
            ->when(
                $excludeId !== null,
                fn ($query) => $query->where('id', '!=', $excludeId)
            )
            ->exists();
    }
}
