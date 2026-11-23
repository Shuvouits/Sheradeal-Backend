<?php

namespace App\Repositories;

use App\Models\GlobalVariant;
use App\Models\GlobalVariantOption;
use Illuminate\Database\Eloquent\Collection;

class GlobalVariantRepository
{
    public function all(?string $search = null): Collection
    {
        return GlobalVariant::query()
            ->with('options')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->ordered()
            ->get();
    }

    public function find(int $id): GlobalVariant
    {
        return GlobalVariant::with('options')->findOrFail($id);
    }

    public function findForUpdate(int $id): GlobalVariant
    {
        return GlobalVariant::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function create(array $data): GlobalVariant
    {
        return GlobalVariant::create($data);
    }

    public function update(
        GlobalVariant $variant,
        array $data
    ): GlobalVariant {
        $variant->update($data);

        return $variant;
    }

    public function createOption(
        GlobalVariant $variant,
        array $data
    ): GlobalVariantOption {
        return $variant->options()->create($data);
    }

    public function updateOption(
        GlobalVariantOption $option,
        array $data
    ): GlobalVariantOption {
        $option->update($data);

        return $option;
    }

    public function deleteOptionsExcept(
        GlobalVariant $variant,
        array $keepIds
    ): void {
        $query = GlobalVariantOption::where(
            'global_variant_id',
            $variant->id
        );

        if (!empty($keepIds)) {
            $query->whereNotIn('id', $keepIds);
        }

        $query->delete();
    }

    public function delete(GlobalVariant $variant): void
    {
        $variant->delete();
    }

    public function slugExists(
        string $slug,
        ?int $excludeId = null
    ): bool {
        return GlobalVariant::query()
            ->where('slug', $slug)
            ->when(
                $excludeId !== null,
                fn ($query) => $query->where('id', '!=', $excludeId)
            )
            ->exists();
    }

    public function optionBelongsToVariant(
        int $optionId,
        int $variantId
    ): bool {
        return GlobalVariantOption::whereKey($optionId)
            ->where('global_variant_id', $variantId)
            ->exists();
    }
}
