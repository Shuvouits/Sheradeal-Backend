<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductDetailRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ProductDetailService
{
    public function __construct(
        private ProductDetailRepository $repository
    ) {}

    public function show(string $slug): array
    {
        $product = $this->repository->findBySlug($slug);

        $brand = $this->repository->brand($product->brand_id);
        $category = $this->repository->category($product->category_id);

        $related = $this->repository->related($product);
        $sameBrand = $this->repository->sameBrand($product);

        return [
            'product' => $this->formatProduct(
                $product,
                $brand,
                $category
            ),
            'related_products' => $related
                ->map(fn ($item) => $this->formatProduct($item))
                ->values()
                ->all(),
            'same_brand_products' => $sameBrand
                ->map(fn ($item) => $this->formatProduct($item))
                ->values()
                ->all(),
            'reviews' => [],
            'review_summary' => [
                'average' => null,
                'count' => 0,
            ],
        ];
    }

    private function formatProduct(
        Product $product,
        ?array $brand = null,
        ?array $category = null
    ): array {
        $attributes = $product->getAttributes();

        $price = (float) ($product->price ?? 0);

        $comparePrice = (float) (
            $product->compare_at_price ?? 0
        );

        $specialPrice = $product->special_price !== null
            ? (float) $product->special_price
            : null;

        $now = now();

        $specialStarted = empty($product->special_price_from)
            || Carbon::parse($product->special_price_from)->lte($now);

        $specialNotEnded = empty($product->special_price_to)
            || Carbon::parse($product->special_price_to)->gte($now);

        $hasSpecial = $specialPrice !== null
            && $specialPrice >= 0
            && $specialPrice < $price
            && $specialStarted
            && $specialNotEnded;

        $sellingPrice = $hasSpecial ? $specialPrice : $price;
        $regularPrice = max($price, $comparePrice);

        $discountPercent = $regularPrice > $sellingPrice
            && $regularPrice > 0
                ? (int) round(
                    (($regularPrice - $sellingPrice) / $regularPrice) * 100
                )
                : 0;

        $images = $product->media
            ->sortByDesc(fn ($item) => (int) ($item->is_cover ?? 0))
            ->map(function ($media) {
                return $this->imageUrl($media);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $trackQuantity = (bool) (
            $product->track_quantity ?? false
        );

        $quantity = (int) ($product->quantity ?? 0);

        $continueSelling = (bool) (
            $product->continue_selling_out_of_stock ?? false
        );

        $inStock = !$trackQuantity
            || $quantity > 0
            || $continueSelling;

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'sku' => $attributes['sku']
                ?? $attributes['product_code']
                ?? null,
            'price' => $regularPrice,
            'selling_price' => $sellingPrice,
            'discount_percent' => $discountPercent,
            'image_url' => $images[0] ?? null,
            'images' => $images,
            'in_stock' => $inStock,
            'track_quantity' => $trackQuantity,
            'available_quantity' => $trackQuantity && !$continueSelling
                ? $quantity
                : null,
            'brand_id' => $product->brand_id,
            'brand' => $brand,
            'category_id' => $product->category_id,
            'category' => $category,
            'origin' => $attributes['origin']
                ?? $attributes['country_of_origin']
                ?? null,
            'size' => $attributes['size'] ?? null,
            'description' => $attributes['description']
                ?? $attributes['short_description']
                ?? null,
            'ingredients' => $attributes['ingredients'] ?? null,
            'how_to_use' => $attributes['how_to_use']
                ?? $attributes['usage_instructions']
                ?? null,
        ];
    }

    private function imageUrl($media): ?string
    {
        if (!$media) {
            return null;
        }

        $value = $media->url
            ?: $media->external_url
            ?: $media->file_path;

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }

        $path = preg_replace(
            '~^(public/|storage/)~',
            '',
            ltrim($value, '/')
        );

        return url(Storage::disk('public')->url($path));
    }
}
