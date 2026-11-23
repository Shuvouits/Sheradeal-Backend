<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Product;
use App\Repositories\CollectionRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CollectionService
{
    public function __construct(
        private CollectionRepository $repository
    ) {}

    public function list(array $filters): array
    {
        $paginator = $this->repository->paginate($filters);

        $paginator->getCollection()->transform(
            fn (Collection $collection) => $this->formatCollection(
                $collection,
                false
            )
        );

        return $paginator->toArray();
    }

    public function statistics(): array
    {
        return $this->repository->statistics();
    }

    public function show(int $id): array
    {
        $collection = $this->repository->findOrFail($id);

        return $this->formatCollection($collection, true);
    }

    public function create(array $data): array
    {
        $productIds = $this->extractProductIds($data);

        $this->validateCollection($data, $productIds);

        $data['slug'] = $this->uniqueSlug(
            $data['slug'] ?? $data['title']
        );

        $data = $this->normalizeRules($data);

        $collection = DB::transaction(function () use (
            $data,
            $productIds
        ) {
            $collection = $this->repository->create($data);

            if ($collection->collection_type === 'manual') {
                $this->repository->syncProducts(
                    $collection,
                    $productIds
                );
            }

            return $collection;
        });

        return $this->show($collection->id);
    }

    public function update(int $id, array $data): array
    {
        $collection = $this->repository->findOrFail($id);

        $productIds = $this->extractProductIds($data);

        $this->validateCollection($data, $productIds);

        if (!empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug(
                $data['slug'],
                $collection->id
            );
        } else {
            unset($data['slug']);
        }

        $data = $this->normalizeRules($data);

        DB::transaction(function () use (
            $collection,
            $data,
            $productIds
        ) {
            $updated = $this->repository->update(
                $collection,
                $data
            );

            $this->repository->syncProducts(
                $updated,
                $updated->collection_type === 'manual'
                    ? $productIds
                    : []
            );
        });

        return $this->show($id);
    }

    public function delete(int $id): void
    {
        $collection = $this->repository->findOrFail($id);

        $coverImage = $collection->cover_image;

        DB::transaction(function () use ($collection) {
            $this->repository->delete($collection);
        });

        if ($coverImage) {
            Storage::disk('public')->delete($coverImage);
        }
    }

    public function uploadCover(
        int $id,
        UploadedFile $image
    ): array {
        $collection = $this->repository->findOrFail($id);

        $oldImage = $collection->cover_image;
        $path = $image->store('collections', 'public');

        if (!$path) {
            throw new \RuntimeException(
                'Unable to store collection cover image.'
            );
        }

        try {
            DB::transaction(function () use ($collection, $path) {
                $this->repository->update(
                    $collection,
                    ['cover_image' => $path]
                );
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        if ($oldImage && $oldImage !== $path) {
            Storage::disk('public')->delete($oldImage);
        }

        return $this->show($id);
    }

    public function removeCover(int $id): array
    {
        $collection = $this->repository->findOrFail($id);

        $oldImage = $collection->cover_image;

        DB::transaction(function () use ($collection) {
            $this->repository->update(
                $collection,
                ['cover_image' => null]
            );
        });

        if ($oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return $this->show($id);
    }

    public function searchProducts(array $filters): array
    {
        $paginator = $this->repository->searchProducts($filters);

        $paginator->getCollection()->transform(
            fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'sku' => $product->sku,
                'price' => (float) ($product->price ?? 0),
                'status' => $product->status,
                'publish_online' => (bool) $product->publish_online,
            ]
        );

        return $paginator->toArray();
    }

    public function storefrontList(): array
    {
        return $this->repository->publishedCollections()
            ->map(fn (Collection $collection) => [
                'id' => $collection->id,
                'title' => $collection->title,
                'slug' => $collection->slug,
                'description' => $collection->description,
                'cover_image_url' => $collection->cover_image_url,
                'display_order' => $collection->display_order,
                'product_count' => $this->repository
                    ->storefrontProductCount($collection),
            ])
            ->values()
            ->all();
    }

    public function storefrontShow(
        string $slug,
        int $limit = 24
    ): array {
        $collection = $this->repository
            ->findPublishedBySlug($slug);

        return [
            'id' => $collection->id,
            'title' => $collection->title,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'cover_image_url' => $collection->cover_image_url,
            'meta_title' => $collection->meta_title,
            'meta_description' => $collection->meta_description,
            'product_count' => $this->repository
                ->storefrontProductCount($collection),
            'products' => $this->repository
                ->storefrontProducts($collection, $limit)
                ->map(fn (Product $product) => $this->formatProduct($product))
                ->values()
                ->all(),
        ];
    }

    private function extractProductIds(array &$data): array
    {
        $ids = array_map(
            'intval',
            $data['product_ids'] ?? []
        );

        unset($data['product_ids']);

        return array_values(array_unique($ids));
    }

    private function validateCollection(
        array $data,
        array $productIds
    ): void {
        if ($data['collection_type'] === 'manual') {
            if (
                $productIds &&
                $this->repository->validProductCount($productIds)
                    !== count($productIds)
            ) {
                throw ValidationException::withMessages([
                    'product_ids' => [
                        'One or more selected products do not exist.',
                    ],
                ]);
            }

            return;
        }

        $hasRule =
            !empty($data['rule_category_id']) ||
            !empty($data['rule_brand_id']) ||
            ($data['rule_min_price'] ?? null) !== null ||
            ($data['rule_max_price'] ?? null) !== null;

        if (!$hasRule) {
            throw ValidationException::withMessages([
                'collection_type' => [
                    'Add at least one condition for an automated collection.',
                ],
            ]);
        }
    }

    private function normalizeRules(array $data): array
    {
        if ($data['collection_type'] === 'manual') {
            $data['rule_category_id'] = null;
            $data['rule_brand_id'] = null;
            $data['rule_min_price'] = null;
            $data['rule_max_price'] = null;
        }

        if (
            $data['collection_type'] === 'automated' &&
            $data['sort_by'] === 'manual'
        ) {
            $data['sort_by'] = 'newest';
        }

        return $data;
    }

    private function uniqueSlug(
        string $value,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'collection';
        }

        $base = substr($base, 0, 220);

        $slug = $base;
        $counter = 2;

        while ($this->repository->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function formatCollection(
        Collection $collection,
        bool $includeProducts
    ): array {
        $count = $collection->collection_type === 'manual'
            ? (
                $collection->products_count
                ?? $collection->products()->count()
            )
            : $this->repository
                ->storefrontProductCount($collection);

        $result = [
            'id' => $collection->id,
            'title' => $collection->title,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'cover_image' => $collection->cover_image,
            'cover_image_url' => $collection->cover_image_url,
            'status' => $collection->status,
            'publish_online' => (bool) $collection->publish_online,
            'publish_pos' => (bool) $collection->publish_pos,
            'collection_type' => $collection->collection_type,
            'sort_by' => $collection->sort_by,
            'display_order' => (int) $collection->display_order,
            'rule_category_id' => $collection->rule_category_id,
            'rule_brand_id' => $collection->rule_brand_id,
            'rule_min_price' => $collection->rule_min_price,
            'rule_max_price' => $collection->rule_max_price,
            'meta_title' => $collection->meta_title,
            'meta_description' => $collection->meta_description,
            'products_count' => (int) $count,
            'created_at' => $collection->created_at,
            'updated_at' => $collection->updated_at,
        ];

        if ($includeProducts) {
            $selected = $this->repository
                ->selectedProducts($collection);

            $result['product_ids'] = $selected
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $result['selected_products'] = $selected
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sku' => $product->sku,
                    'price' => (float) ($product->price ?? 0),
                    'display_order' => (int) $product->pivot->display_order,
                ])
                ->values()
                ->all();
        }

        return $result;
    }

    private function formatProduct(Product $product): array
    {
        $price = (float) ($product->price ?? 0);

        $special = $product->special_price !== null
            ? (float) $product->special_price
            : null;

        $now = now();

        $specialStarted = !$product->special_price_from
            || Carbon::parse($product->special_price_from)->lte($now);

        $specialNotEnded = !$product->special_price_to
            || Carbon::parse($product->special_price_to)->gte($now);

        $validSpecial = $special !== null
            && $special >= 0
            && $special < $price
            && $specialStarted
            && $specialNotEnded;

        $sellingPrice = $validSpecial ? $special : $price;

        $originalPrice = max(
            $price,
            (float) ($product->compare_at_price ?? 0)
        );

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
            'image_url' => $this->productImageUrl($media),
            'in_stock' => !$product->track_quantity
                || (int) $product->quantity > 0
                || (bool) $product->continue_selling_out_of_stock,
        ];
    }

    private function productImageUrl($media): ?string
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

        return url(
            Storage::disk('public')->url($path)
        );
    }
}
