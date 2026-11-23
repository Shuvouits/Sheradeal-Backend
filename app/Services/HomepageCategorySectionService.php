<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\HomepageCategorySection;
use App\Models\Product;
use App\Repositories\CollectionRepository;
use App\Repositories\HomepageCategorySectionRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomepageCategorySectionService
{
    public function __construct(
        private HomepageCategorySectionRepository $sections,
        private CollectionRepository $collections
    ) {}

    public function storefront(): array
    {
        return $this->sections->visible()
            ->map(fn (HomepageCategorySection $section) =>
                $this->formatSection($section)
            )
            ->filter(fn (array $section) =>
                count($section['products']) > 0
            )
            ->values()
            ->all();
    }

    public function manage(): array
    {
        return $this->sections->all()
            ->map(fn (HomepageCategorySection $section) =>
                $this->formatSection($section)
            )
            ->values()
            ->all();
    }

    public function save(array $data, ?int $id = null): array
    {
        $data['category_id'] = null;
        $data['selection_mode'] = 'automatic';

        $section = DB::transaction(function () use ($data, $id) {
            if ($id) {
                return $this->sections->update(
                    $this->sections->findOrFail($id),
                    $data
                );
            }

            return $this->sections->create($data);
        });

        return $this->formatSection(
            $section->load('collection')
        );
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $section = $this->sections->findOrFail($id);

            $this->sections->delete($section);
        });
    }

    private function formatSection(
        HomepageCategorySection $section
    ): array {
        $collection = $section->collection;

        $products = [];

        if ($this->isPublished($collection)) {
            $products = $this->collections
                ->storefrontProducts(
                    $collection,
                    (int) $section->product_limit
                )
                ->map(fn (Product $product) =>
                    $this->formatProduct($product)
                )
                ->values()
                ->all();
        }

        return [
            'id' => $section->id,
            'title' => $section->title,
            'collection_id' => $section->collection_id,
            'collection_name' => $collection?->title,
            'collection_slug' => $collection?->slug,
            'collection_type' => $collection?->collection_type,
            'product_limit' => (int) $section->product_limit,
            'display_order' => (int) $section->display_order,
            'is_active' => (bool) $section->is_active,
            'products' => $products,
        ];
    }

    private function isPublished(?Collection $collection): bool
    {
        return $collection !== null
            && $collection->status === 'active'
            && (bool) $collection->publish_online;
    }

    private function formatProduct(Product $product): array
    {
        $price = (float) ($product->price ?? 0);
        $comparePrice = (float) ($product->compare_at_price ?? 0);

        $specialPrice = $product->special_price !== null
            ? (float) $product->special_price
            : null;

        $now = now();

        $specialStarted = !$product->special_price_from
            || Carbon::parse($product->special_price_from)->lte($now);

        $specialNotEnded = !$product->special_price_to
            || Carbon::parse($product->special_price_to)->gte($now);

        $hasSpecial = $specialPrice !== null
            && $specialPrice >= 0
            && $specialPrice < $price
            && $specialStarted
            && $specialNotEnded;

        $sellingPrice = $hasSpecial ? $specialPrice : $price;
        $originalPrice = max($price, $comparePrice);

        $discountPercent = $originalPrice > $sellingPrice
            && $originalPrice > 0
                ? (int) round(
                    (($originalPrice - $sellingPrice) / $originalPrice) * 100
                )
                : 0;

        $media = $product->media
            ->sortByDesc(fn ($item) => (int) $item->is_cover)
            ->first();

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'price' => $originalPrice,
            'selling_price' => $sellingPrice,
            'discount_percent' => $discountPercent,
            'image_url' => $this->imageUrl($media),
            'in_stock' => !$product->track_quantity
                || (int) $product->quantity > 0
                || (bool) $product->continue_selling_out_of_stock,
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

        if (!is_string($value) || $value === '') {
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
