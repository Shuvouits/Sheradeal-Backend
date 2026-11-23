<?php

namespace App\Repositories;

use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Database\Eloquent\Collection;

class ProductVariantRepository
{
    /*
    |--------------------------------------------------------------------------
    | Get Product Variants
    |--------------------------------------------------------------------------
    */

    public function getByProduct(int $productId): Collection
    {
        return ProductVariant::query()
            ->where('product_id', $productId)
            ->with([
                'media',
                'values.globalVariant',
                'values.option',
            ])
            ->ordered()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Find Variant
    |--------------------------------------------------------------------------
    */

    public function find(int $id): ProductVariant
    {
        return ProductVariant::query()
            ->with([
                'product',
                'media',
                'values.globalVariant',
                'values.option',
            ])
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Lock Variant for Stock Updates
    |--------------------------------------------------------------------------
    */

    public function findForUpdate(int $id): ProductVariant
    {
        return ProductVariant::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Variant
    |--------------------------------------------------------------------------
    */

    public function create(array $data): ProductVariant
    {
        return ProductVariant::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Variant
    |--------------------------------------------------------------------------
    */

    public function update(
        ProductVariant $variant,
        array $data
    ): ProductVariant {
        $variant->update($data);

        return $variant->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Global Variant Values
    |--------------------------------------------------------------------------
    */

    public function syncValues(
        ProductVariant $variant,
        array $values
    ): void {
        // IDs must be validated by the Service first.

        $variant->values()->delete();

        foreach ($values as $value) {
            $variant->values()->create([
                'global_variant_id' =>
                    $value['global_variant_id'],

                'global_variant_option_id' =>
                    $value['global_variant_option_id'],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Check Combination
    |--------------------------------------------------------------------------
    */

    public function combinationExists(
        int $productId,
        string $combinationKey,
        ?int $excludeId = null
    ): bool {
        return ProductVariant::withTrashed()
            ->where('product_id', $productId)
            ->where('combination_key', $combinationKey)
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
        return ProductVariant::withTrashed()
            ->where('sku', $sku)
            ->when(
                $excludeId !== null,
                fn ($query) => $query->where('id', '!=', $excludeId)
            )
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Variant
    |--------------------------------------------------------------------------
    */

    public function delete(ProductVariant $variant): bool
    {
        return (bool) $variant->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Variant
    |--------------------------------------------------------------------------
    */

    public function restore(int $id): ProductVariant
    {
        $variant = ProductVariant::onlyTrashed()
            ->findOrFail($id);

        $variant->restore();

        return $variant->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Variant Stock Summary
    |--------------------------------------------------------------------------
    */

    public function totalStock(int $productId): int
    {
        return (int) ProductVariant::query()
            ->where('product_id', $productId)
            ->sum('quantity');
    }
}
