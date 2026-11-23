<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\StorefrontProductSearchRepository;
use Illuminate\Support\Facades\Storage;

class StorefrontProductSearchService
{
    public function __construct(
        private StorefrontProductSearchRepository $repository
    ) {}

    public function search(array $filters): array
    {
        $pagination = $this->repository->search($filters);

        return [
            'products' => $pagination->getCollection()
                ->map(fn (Product $product) => $this->formatProduct($product))
                ->values()
                ->all(),

            'pagination' => [
                'current_page' => $pagination->currentPage(),
                'last_page' => $pagination->lastPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
                'from' => $pagination->firstItem(),
                'to' => $pagination->lastItem(),
            ],

            'search' => trim((string) ($filters['search'] ?? '')),
            'sort' => $filters['sort'] ?? 'relevance',
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
            $special = (float) $attributes['special_price'];

            $startsAt = $attributes['special_price_from'] ?? null;
            $endsAt = $attributes['special_price_to'] ?? null;

            if (
                $special < $basePrice &&
                (!$startsAt || now()->gte($startsAt)) &&
                (!$endsAt || now()->lte($endsAt))
            ) {
                $sellingPrice = $special;
            }
        }

        $regularPrice = max(
            $basePrice,
            (float) ($attributes['compare_at_price'] ?? 0),
            $sellingPrice
        );

        $media = $product->media
            ->sortByDesc(
                fn ($item) => (int) ($item->is_cover ?? 0)
            )
            ->first();

        $image = $media?->url
            ?: $media?->external_url
            ?: $media?->file_path;

        $trackQuantity = (bool) ($attributes['track_quantity'] ?? false);
        $quantity = (int) ($attributes['quantity'] ?? 0);
        $continueSelling = (bool) (
            $attributes['continue_selling_out_of_stock'] ?? false
        );

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'price' => $regularPrice,
            'selling_price' => $sellingPrice,
            'discount_percent' => $regularPrice > $sellingPrice
                ? (int) round(
                    (($regularPrice - $sellingPrice) / $regularPrice) * 100
                )
                : 0,
            'image_url' => $this->imageUrl($image),
            'in_stock' => !$trackQuantity
                || $quantity > 0
                || $continueSelling,
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        $path = preg_replace(
            '~^(public/|storage/)~',
            '',
            ltrim($path, '/')
        );

        return url(Storage::disk('public')->url($path));
    }
}
