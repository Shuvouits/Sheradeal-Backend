<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        protected ProductRepository $products
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Product Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->products->paginate($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Statistics
    |--------------------------------------------------------------------------
    */

    public function statistics(): array
    {
        return $this->products->statistics();
    }

    /*
    |--------------------------------------------------------------------------
    | Find Product
    |--------------------------------------------------------------------------
    */

    public function find(int $id): Product
    {
        return $this->products->find($id);
    }

    public function findBySlug(string $slug): Product
    {
        return $this->products->findBySlug($slug);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Product
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            $this->validateBusinessRules($data);

            $title = trim($data['title']);

            $slug = $this->generateSlug(
                $data['slug'] ?? $title
            );

            $payload = $this->preparePayload($data);

            $payload['title'] = $title;
            $payload['slug'] = $slug;

            // New products start without variant combinations.
            // VariantService manages this flag later.
            $payload['has_variants'] = false;

            $payload['published_at'] =
                $this->resolvePublishedAt($payload, null);

            $product = $this->products->create($payload);

            return $this->products->find($product->id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    */

    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {

            $product = $this->products->findForUpdate($id);

            $payload = $this->preparePayload($data);

            $this->validateBusinessRules(
                $payload,
                $product
            );

            if (array_key_exists('title', $payload)) {
                $payload['title'] = trim($payload['title']);
            }

            if (array_key_exists('slug', $payload)) {
                $payload['slug'] = $this->generateSlug(
                    $payload['slug'],
                    $product->id
                );
            }

            // Keep existing slug when title alone changes.

            if (
                array_key_exists('status', $payload) ||
                array_key_exists('publish_online', $payload)
            ) {
                $payload['published_at'] =
                    $this->resolvePublishedAt(
                        $payload,
                        $product
                    );
            }

            $this->products->update($product, $payload);

            return $this->products->find($product->id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Product
    |--------------------------------------------------------------------------
    */

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {

            $product = $this->products->findForUpdate($id);

            $this->products->delete($product);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Product
    |--------------------------------------------------------------------------
    */

    public function restore(int $id): Product
    {
        return DB::transaction(function () use ($id) {

            $restored = $this->products->restore($id);

            return $this->products->find($restored->id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Product Data
    |--------------------------------------------------------------------------
    */

    private function preparePayload(array $data): array
    {
        $allowed = [
            'title',
            'slug',
            'product_type',
            'overview',
            'description',
            'specifications',

            'category_id',
            'brand_id',
            'vendor_name',
            'manufacturer',
            'distributed_by',
            'tags',

            'status',
            'is_featured',
            'publish_online',
            'publish_pos',

            'is_final_sale',
            'return_window_days',
            'cod_advance_required',
            'cod_advance_amount',

            'price',
            'compare_at_price',
            'cost_per_item',
            'charge_tax',
            'unit_price',
            'unit_price_measurement',
            'special_price',
            'special_price_from',
            'special_price_to',

            'sku',
            'barcode',
            'barcode_standard',
            'identifier_source',
            'quantity',
            'track_quantity',
            'continue_selling_out_of_stock',

            'min_order_quantity',
            'quantity_multiple',
            'max_order_quantity',

            'requires_shipping',
            'weight',
            'weight_unit',
            'length',
            'width',
            'height',
            'dimension_unit',
            'country_of_origin',
            'hs_code',
            'customs_description',

            'meta_title',
            'meta_description',

            'external_url',
            'import_source',
            'external_id',
            'additional_attributes',

            'display_order',
        ];

        $payload = Arr::only($data, $allowed);

        foreach ([
            'sku',
            'barcode',
            'vendor_name',
            'manufacturer',
            'distributed_by',
            'meta_title',
            'meta_description',
            'external_url',
        ] as $field) {
            if (
                array_key_exists($field, $payload) &&
                is_string($payload[$field])
            ) {
                $value = trim($payload[$field]);

                $payload[$field] = $value === ''
                    ? null
                    : $value;
            }
        }

        return $payload;
    }

    /*
    |--------------------------------------------------------------------------
    | Unique Product Slug
    |--------------------------------------------------------------------------
    */

    private function generateSlug(
        string $value,
        ?int $excludeId = null
    ): string {
        $base = Str::slug($value);

        if ($base === '') {
            throw ValidationException::withMessages([
                'slug' => ['Enter a valid product URL handle.'],
            ]);
        }

        $base = substr($base, 0, 235);

        $slug = $base;
        $counter = 2;

        while ($this->products->slugExists(
            $slug,
            $excludeId
        )) {
            $suffix = '-' . $counter;

            $slug = substr(
                $base,
                0,
                255 - strlen($suffix)
            ) . $suffix;

            $counter++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Business Validation
    |--------------------------------------------------------------------------
    */

    private function validateBusinessRules(
        array $data,
        ?Product $product = null
    ): void {
        $this->validateOrganization($data);

        $this->validateIdentifiers($data, $product);

        $this->validatePricing($data, $product);

        $this->validateQuantityRules($data, $product);

        $this->validateProductStatus($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Brand and Category Validation
    |--------------------------------------------------------------------------
    */

    private function validateOrganization(array $data): void
    {
        if (!empty($data['category_id'])) {
            $exists = DB::table('categories')
                ->where('id', $data['category_id'])
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'category_id' => ['Selected category does not exist.'],
                ]);
            }
        }

        if (!empty($data['brand_id'])) {
            $exists = DB::table('brands')
                ->where('id', $data['brand_id'])
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'brand_id' => ['Selected brand does not exist.'],
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SKU and Barcode Validation
    |--------------------------------------------------------------------------
    */

    private function validateIdentifiers(
        array $data,
        ?Product $product
    ): void {
        $excludeId = $product?->id;

        foreach (['sku', 'barcode'] as $field) {

            if (empty($data[$field])) {
                continue;
            }

            $value = trim($data[$field]);

            // Check product-level identifier.
            $productExists = Product::withTrashed()
                ->where($field, $value)
                ->when(
                    $excludeId !== null,
                    fn ($query) =>
                        $query->where('id', '!=', $excludeId)
                )
                ->exists();

            // Check variant-level identifier.
            $variantExists = ProductVariant::withTrashed()
                ->where($field, $value)
                ->exists();

            if ($productExists || $variantExists) {
                throw ValidationException::withMessages([
                    $field => [
                        strtoupper($field) . ' already exists.',
                    ],
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Pricing Rules
    |--------------------------------------------------------------------------
    */

    private function validatePricing(
        array $data,
        ?Product $product
    ): void {
        $price = $data['price']
            ?? $product?->price
            ?? 0;

        $compareAt = array_key_exists(
            'compare_at_price',
            $data
        )
            ? $data['compare_at_price']
            : $product?->compare_at_price;

        if (
            $compareAt !== null &&
            (float) $compareAt < (float) $price
        ) {
            throw ValidationException::withMessages([
                'compare_at_price' => [
                    'Compare-at price cannot be lower than the selling price.',
                ],
            ]);
        }

        $specialPrice = array_key_exists(
            'special_price',
            $data
        )
            ? $data['special_price']
            : $product?->special_price;

        if (
            $specialPrice !== null &&
            (float) $specialPrice > (float) $price
        ) {
            throw ValidationException::withMessages([
                'special_price' => [
                    'Special price cannot exceed the regular price.',
                ],
            ]);
        }

        $specialFrom = $data['special_price_from']
            ?? $product?->special_price_from;

        $specialTo = $data['special_price_to']
            ?? $product?->special_price_to;

        if (
            $specialFrom &&
            $specialTo &&
            \Illuminate\Support\Carbon::parse($specialTo)
                ->lessThanOrEqualTo(
                    \Illuminate\Support\Carbon::parse($specialFrom)
                )
        ) {
            throw ValidationException::withMessages([
                'special_price_to' => [
                    'Special price end date must be after the start date.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Quantity Rules
    |--------------------------------------------------------------------------
    */

    private function validateQuantityRules(
        array $data,
        ?Product $product
    ): void {
        $minimum = (int) (
            $data['min_order_quantity']
            ?? $product?->min_order_quantity
            ?? 1
        );

        $multiple = (int) (
            $data['quantity_multiple']
            ?? $product?->quantity_multiple
            ?? 1
        );

        $maximum = array_key_exists(
            'max_order_quantity',
            $data
        )
            ? $data['max_order_quantity']
            : $product?->max_order_quantity;

        if ($minimum < 1 || $multiple < 1) {
            throw ValidationException::withMessages([
                'min_order_quantity' => [
                    'Minimum quantity and quantity multiple must be at least 1.',
                ],
            ]);
        }

        if (
            $maximum !== null &&
            (int) $maximum < $minimum
        ) {
            throw ValidationException::withMessages([
                'max_order_quantity' => [
                    'Maximum quantity cannot be less than minimum quantity.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Product Status
    |--------------------------------------------------------------------------
    */

    private function validateProductStatus(array $data): void
    {
        if (
            isset($data['status']) &&
            !in_array(
                $data['status'],
                ['active', 'draft', 'archived'],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'status' => ['Invalid product status.'],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Published At
    |--------------------------------------------------------------------------
    */

    private function resolvePublishedAt(
        array $data,
        ?Product $product
    ) {
        $status = $data['status']
            ?? $product?->status
            ?? 'draft';

        $publishOnline = $data['publish_online']
            ?? $product?->publish_online
            ?? true;

        if ($status === 'active' && $publishOnline) {
            return $product?->published_at ?? now();
        }

        return null;
    }
}
