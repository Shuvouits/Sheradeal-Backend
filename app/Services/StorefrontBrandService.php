<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\StorefrontBrandRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StorefrontBrandService
{
    public function __construct(
        private StorefrontBrandRepository $repository
    ) {}

    public function show(
        string $slug,
        array $filters = []
    ): array {
        $brand = $this->repository->findBySlug($slug);

        if (!$brand) {
            abort(404, 'Brand not found.');
        }

        $pagination = $this->repository->products(
            (int) $brand->id,
            $filters
        );

        $products = $pagination->getCollection()
            ->map(function (Product $product) {
                return $this->formatProduct($product);
            })
            ->values()
            ->all();

        $brandName = $brand->name
            ?? $brand->title
            ?? 'Brand';

        $description = $brand->description ?? null;

        $logoPath = $brand->logo
            ?? $brand->logo_path
            ?? $brand->image
            ?? null;

        $bannerPath = $brand->banner
            ?? $brand->banner_image
            ?? $brand->cover_image
            ?? null;

        return [
            'brand' => [
                'id' => (int) $brand->id,
                'name' => $brandName,
                'slug' => $brand->slug,
                'description' => $description,
                'logo_url' => $this->assetUrl($logoPath),
                'banner_url' => $this->assetUrl($bannerPath),
                'meta_title' => $brand->meta_title ?? null,
                'meta_description' => $brand->meta_description ?? null,
            ],

            'products' => $products,

            'pagination' => [
                'current_page' => $pagination->currentPage(),
                'last_page' => $pagination->lastPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
                'from' => $pagination->firstItem(),
                'to' => $pagination->lastItem(),
                'has_more_pages' => $pagination->hasMorePages(),
            ],
        ];
    }

    private function formatProduct(Product $product): array
    {
        $attributes = $product->getAttributes();

        $basePrice = (float) ($attributes['price'] ?? 0);

        $sellingPrice = $basePrice;

        if (
            isset($attributes['selling_price']) &&
            is_numeric($attributes['selling_price'])
        ) {
            $sellingPrice = (float) $attributes['selling_price'];
        } elseif (
            isset($attributes['special_price']) &&
            is_numeric($attributes['special_price'])
        ) {
            $specialPrice = (float) $attributes['special_price'];

            $startsAt = $attributes['special_price_from'] ?? null;
            $endsAt = $attributes['special_price_to'] ?? null;

            $started = !$startsAt || now()->gte($startsAt);
            $notEnded = !$endsAt || now()->lte($endsAt);

            if (
                $specialPrice < $basePrice &&
                $started &&
                $notEnded
            ) {
                $sellingPrice = $specialPrice;
            }
        }

        $regularPrice = max(
            $basePrice,
            (float) ($attributes['compare_at_price'] ?? 0),
            $sellingPrice
        );

        $discountPercent = $regularPrice > $sellingPrice
            && $regularPrice > 0
                ? round(
                    (($regularPrice - $sellingPrice) / $regularPrice) * 100
                )
                : 0;

        $media = $product->media
            ->sortByDesc(
                fn ($item) => (int) ($item->is_cover ?? 0)
            )
            ->first();

        $imagePath = $media?->url
            ?: $media?->external_url
            ?: $media?->file_path;

        $trackQuantity = (bool) (
            $attributes['track_quantity'] ?? false
        );

        $quantity = (int) (
            $attributes['quantity'] ?? 0
        );

        $continueSelling = (bool) (
            $attributes['continue_selling_out_of_stock'] ?? false
        );

        $inStock = !$trackQuantity
            || $quantity > 0
            || $continueSelling;

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'price' => $regularPrice,
            'selling_price' => $sellingPrice,
            'discount_percent' => (int) $discountPercent,
            'image_url' => $this->assetUrl($imagePath),
            'in_stock' => $inStock,
            'brand_id' => $product->brand_id,
        ];
    }

    private function assetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url(
            Storage::disk('public')->url(
                preg_replace(
                    '~^(public/|storage/)~',
                    '',
                    ltrim($path, '/')
                )
            )
        );
    }
}
