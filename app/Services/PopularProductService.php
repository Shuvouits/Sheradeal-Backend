<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\PopularProductRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PopularProductService
{
    public function __construct(
        private PopularProductRepository $repository
    ) {}

    public function update(
        int $productId,
        bool $isPopular,
        int $sortOrder = 0
    ): array {
        $product = $this->repository->findOrFail($productId);

        $updated = $this->repository->updatePopularity(
            $product,
            $isPopular,
            $sortOrder
        );

        return [
            'id' => $updated->id,
            'title' => $updated->title,
            'is_popular' => (bool) $updated->is_popular,
            'popular_sort_order' => (int) $updated->popular_sort_order,
        ];
    }

    public function list(int $limit = 12): array
    {
        return $this->repository
            ->getPopularProducts($limit)
            ->map(fn (Product $product) => $this->formatProduct($product))
            ->values()
            ->all();
    }

    private function formatProduct(Product $product): array
    {
        $regularPrice = (float) $product->price;

        $specialPrice = $product->special_price !== null
            ? (float) $product->special_price
            : null;

        $now = now();

        $specialStarted =
            !$product->special_price_from ||
            Carbon::parse($product->special_price_from)->lte($now);

        $specialNotEnded =
            !$product->special_price_to ||
            Carbon::parse($product->special_price_to)->gte($now);

        $hasDiscount =
            $specialPrice !== null &&
            $specialPrice >= 0 &&
            $specialPrice < $regularPrice &&
            $specialStarted &&
            $specialNotEnded;

        $sellingPrice = $hasDiscount
            ? $specialPrice
            : $regularPrice;

        $discountPercent = $hasDiscount && $regularPrice > 0
            ? (int) round(
                (($regularPrice - $sellingPrice) / $regularPrice) * 100
            )
            : 0;

        $cover = $product->media->first();

        $imageUrl = null;

        if ($cover) {
            if ($cover->file_path) {
                $imageUrl = Storage::disk(
                    $cover->disk ?: 'public'
                )->url($cover->file_path);
            } elseif ($cover->external_url) {
                $imageUrl = $cover->external_url;
            }
        }

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,

            'price' => $regularPrice,
            'selling_price' => $sellingPrice,
            'discount_percent' => $discountPercent,

            'image_url' => $imageUrl,

            'is_popular' => (bool) $product->is_popular,
            'popular_sort_order' => (int) $product->popular_sort_order,

            'in_stock' =>
                !$product->track_quantity ||
                $product->quantity > 0 ||
                (bool) $product->continue_selling_out_of_stock,
        ];
    }
}
