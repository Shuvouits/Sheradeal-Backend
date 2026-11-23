<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\NewArrivalProductRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class NewArrivalProductService
{
    public function __construct(
        private NewArrivalProductRepository $repository
    ) {}

    public function toggle(int $productId, bool $enabled): array
    {
        $product = $this->repository->findOrFail($productId);

        $product = $this->repository->updateStatus(
            $product,
            $enabled
        );

        return [
            'id' => $product->id,
            'title' => $product->title,
            'is_new_arrival' => (bool) $product->is_new_arrival,
        ];
    }

    public function storefront(int $limit = 15): array
    {
        return $this->repository
            ->getStorefrontProducts($limit)
            ->map(fn (Product $product) => $this->formatProduct($product))
            ->values()
            ->all();
    }

    private function formatProduct(Product $product): array
    {
        $price = (float) ($product->price ?? 0);

        $specialPrice = $product->special_price !== null
            ? (float) $product->special_price
            : null;

        $now = now();

        $specialStarted = !$product->special_price_from
            || Carbon::parse($product->special_price_from)->lte($now);

        $specialNotEnded = !$product->special_price_to
            || Carbon::parse($product->special_price_to)->gte($now);

        $hasSpecialPrice = $specialPrice !== null
            && $specialPrice >= 0
            && $specialPrice < $price
            && $specialStarted
            && $specialNotEnded;

        $sellingPrice = $hasSpecialPrice
            ? $specialPrice
            : $price;

        $compareAtPrice = (float) (
            $product->compare_at_price ?? 0
        );

        $originalPrice = $hasSpecialPrice
            ? max($price, $compareAtPrice)
            : max($price, $compareAtPrice);

        $discountPercent = $originalPrice > $sellingPrice
            && $originalPrice > 0
                ? (int) round(
                    (($originalPrice - $sellingPrice) / $originalPrice) * 100
                )
                : 0;

        $cover = $product->media
            ->sortByDesc(fn ($media) => (int) $media->is_cover)
            ->first();

        $imageUrl = $this->getImageUrl($cover);

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,

            'price' => $originalPrice,
            'selling_price' => $sellingPrice,
            'discount_percent' => $discountPercent,

            'image_url' => $imageUrl,

            'is_new_arrival' => (bool) $product->is_new_arrival,

            'in_stock' => !$product->track_quantity
                || (int) $product->quantity > 0
                || (bool) $product->continue_selling_out_of_stock,
        ];
    }

    private function getImageUrl($media): ?string
    {
        if (!$media) {
            return null;
        }

        $value = $media->url
            ?: $media->external_url
            ?: $media->file_path;

        if (!$value || !is_string($value)) {
            return null;
        }

        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }

        $path = ltrim($value, '/');

        $path = preg_replace(
            '~^(public/|storage/)~',
            '',
            $path
        );

        return url(
            Storage::disk('public')->url($path)
        );
    }
}
