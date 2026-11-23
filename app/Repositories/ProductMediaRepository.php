<?php

namespace App\Repositories;

use App\Models\ProductMedia;
use Illuminate\Database\Eloquent\Collection;

class ProductMediaRepository
{
    /*
    |--------------------------------------------------------------------------
    | Get Product Media
    |--------------------------------------------------------------------------
    */

    public function getByProduct(int $productId): Collection
    {
        return ProductMedia::query()
            ->where('product_id', $productId)
            ->ordered()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Find Media
    |--------------------------------------------------------------------------
    */

    public function find(int $id): ProductMedia
    {
        return ProductMedia::findOrFail($id);
    }

    public function findForProduct(
        int $productId,
        int $mediaId
    ): ProductMedia {
        return ProductMedia::query()
            ->where('product_id', $productId)
            ->findOrFail($mediaId);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Media Record
    |--------------------------------------------------------------------------
    */

    public function create(array $data): ProductMedia
    {
        return ProductMedia::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Media Record
    |--------------------------------------------------------------------------
    */

    public function update(
        ProductMedia $media,
        array $data
    ): ProductMedia {
        $media->update($data);

        return $media->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Set Cover Image
    |--------------------------------------------------------------------------
    */

    public function setCover(
        int $productId,
        int $mediaId
    ): void {
        $media = $this->findForProduct(
            $productId,
            $mediaId
        );

        if ($media->media_type !== 'image') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'media_id' => [
                    'Only an image can be selected as cover.',
                ],
            ]);
        }

        // Must be called inside a transaction with
        // the owning product row locked.

        ProductMedia::where('product_id', $productId)
            ->update(['is_cover' => false]);

        $media->update(['is_cover' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reorder Media
    |--------------------------------------------------------------------------
    */

    public function reorder(
        int $productId,
        array $mediaIds
    ): void {
        $existingIds = ProductMedia::query()
            ->where('product_id', $productId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $submittedIds = collect($mediaIds)
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($existingIds !== $submittedIds) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'media_ids' => [
                    'Provide all product media IDs exactly once.',
                ],
            ]);
        }

        foreach ($mediaIds as $index => $mediaId) {
            ProductMedia::query()
                ->where('product_id', $productId)
                ->whereKey($mediaId)
                ->update([
                    'display_order' => $index + 1,
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Media Record
    |--------------------------------------------------------------------------
    */

    public function delete(ProductMedia $media): bool
    {
        return (bool) $media->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Cover Image
    |--------------------------------------------------------------------------
    */

    public function getCover(int $productId): ?ProductMedia
    {
        return ProductMedia::query()
            ->where('product_id', $productId)
            ->where('is_cover', true)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Media Count
    |--------------------------------------------------------------------------
    */

    public function countByProduct(int $productId): int
    {
        return ProductMedia::query()
            ->where('product_id', $productId)
            ->count();
    }
}
