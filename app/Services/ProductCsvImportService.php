<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ProductCsvImportService
{
    private array $brandCache = [];
    private array $categoryCache = [];

    public function __construct(
        protected ProductService $productService
    ) {}

    public function importRow(array $row): string
    {
        $barcode = $this->clean($row['Barcode'] ?? '');

        if ($barcode === '') {
            throw ValidationException::withMessages([
                'Barcode' => ['Barcode is required for CSV import.'],
            ]);
        }

        // Create-only import: never overwrite an existing product.
        if (
            Product::withTrashed()
                ->where('barcode', $barcode)
                ->exists()
        ) {
            return 'skipped';
        }

        $title = $this->clean($row['Item Name'] ?? '');

        if ($title === '') {
            throw ValidationException::withMessages([
                'Item Name' => ['Product name is required.'],
            ]);
        }

        $price = $this->money($row['price'] ?? null);

        if ($price === null) {
            throw ValidationException::withMessages([
                'price' => ['A valid product price is required.'],
            ]);
        }

        $type = strtolower(
            $this->clean($row['Type'] ?? 'simple')
        );

        $status = strtolower(
            $this->clean($row['Status'] ?? '')
        );

        $stock = $this->stock($row['Stock'] ?? '0');

        $special = $this->money(
            $row['Special Price'] ?? null
        );

        // Invalid discounts must not prevent the product import.
        if ($special !== null && $special > $price) {
            $special = null;
        }

        $specialFrom = $this->dateValue(
            $row['Special Price From'] ?? null
        );

        $specialTo = $this->dateValue(
            $row['Special Price To'] ?? null
        );

        if (
            $specialFrom &&
            $specialTo &&
            $specialTo <= $specialFrom
        ) {
            $specialFrom = null;
            $specialTo = null;
        }

        return DB::transaction(function () use (
            $row,
            $barcode,
            $title,
            $price,
            $type,
            $status,
            $stock,
            $special,
            $specialFrom,
            $specialTo
        ) {
            $brandId = $this->resolveBrand(
                $row['Brand'] ?? ''
            );

            $categoryId = $this->resolveCategory(
                $row['Category'] ?? ''
            );

            $attributes = [
                'attribute_set' => $this->clean(
                    $row['Attribute Set'] ?? ''
                ),
                'category_path' => $this->clean(
                    $row['Category Path'] ?? ''
                ),
                'available_stock_original' => $this->clean(
                    $row['Available Stock'] ?? ''
                ),
                'source_type' => $type,
                'source_status' => $status,
                'source_image_url' => $this->clean(
                    $row['img_url'] ?? ''
                ),
            ];

            $payload = [
                'title' => $title,
                'barcode' => $barcode,
                'product_type' => $type === 'virtual'
                    ? 'digital'
                    : 'physical',
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'manufacturer' => $this->nullable(
                    $row['Manufacturer'] ?? null
                ),
                'distributed_by' => $this->nullable(
                    $row['Distributed By'] ?? null
                ),
                'country_of_origin' => $this->nullable(
                    $row['Country Of Manufacturer'] ?? null
                ),
                'price' => $price,
                'special_price' => $special,
                'special_price_from' => $specialFrom,
                'special_price_to' => $specialTo,
                'status' => $status === 'enabled'
                    ? 'active'
                    : 'draft',
                'publish_online' => $status === 'enabled',
                'publish_pos' => false,
                'track_quantity' => true,
                'continue_selling_out_of_stock' => false,
                'requires_shipping' => $type !== 'virtual',
                'external_url' => $this->nullable(
                    $row['link_url'] ?? null
                ),
                'import_source' => 'csv',
                'external_id' => $barcode,
                'additional_attributes' => $attributes,
            ];

            // Only apply the original schedule to a valid discount.
            if ($special === null) {
                $payload['special_price_from'] = null;
                $payload['special_price_to'] = null;
            }

            $product = $this->productService->create($payload);

            if ($stock > 0) {
                $product->update([
                    'quantity' => $stock,
                ]);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'movement_type' => 'opening',
                    'quantity_change' => $stock,
                    'quantity_before' => 0,
                    'quantity_after' => $stock,
                    'reference_type' => 'csv_import',
                    'reference_id' => null,
                    'reason' => 'Initial stock imported from CSV',
                    'notes' => 'Original CSV Stock field',
                    'created_by' => null,
                ]);
            }

            $this->createImage(
                $product,
                $row['img_url'] ?? ''
            );

            return 'created';
        });
    }

    private function resolveBrand(?string $name): ?int
    {
        $name = $this->clean($name);

        if (
            $name === '' ||
            in_array(
                strtolower($name),
                ['n/a', 'none', 'unassigned'],
                true
            )
        ) {
            return null;
        }

        $key = mb_strtolower($name);

        if (isset($this->brandCache[$key])) {
            return $this->brandCache[$key];
        }

        $brand = Brand::query()
            ->where('name', $name)
            ->first();

        if (!$brand) {
            $slug = Str::slug($name);

            if ($slug === '') {
                return null;
            }

            $brand = Brand::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }

        return $this->brandCache[$key] = $brand->id;
    }

    private function resolveCategory(?string $name): ?int
    {
        $name = $this->clean($name);

        if ($name === '') {
            return null;
        }

        $key = mb_strtolower($name);

        if (isset($this->categoryCache[$key])) {
            return $this->categoryCache[$key];
        }

        $category = Category::query()
            ->where('name', $name)
            ->first();

        if (!$category) {
            $slug = Str::slug($name);

            if ($slug === '') {
                return null;
            }

            $category = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }

        return $this->categoryCache[$key] = $category->id;
    }

    private function createImage(
        Product $product,
        ?string $url
    ): void {
        $url = $this->clean($url);

        if ($url === '') {
            return;
        }

        if (
            !filter_var($url, FILTER_VALIDATE_URL) ||
            !in_array(
                strtolower(parse_url($url, PHP_URL_SCHEME) ?: ''),
                ['http', 'https'],
                true
            )
        ) {
            return;
        }

        ProductMedia::create([
            'product_id' => $product->id,
            'media_type' => 'image',
            'disk' => 'public',
            'file_path' => null,
            'external_url' => $url,
            'original_filename' => null,
            'alt_text' => $product->title,
            'is_cover' => true,
            'display_order' => 1,
        ]);
    }

    private function clean(?string $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function nullable(?string $value): ?string
    {
        $value = $this->clean($value);

        return $value === '' ? null : $value;
    }

    private function money(?string $value): ?float
    {
        $value = str_replace(
            ',',
            '',
            $this->clean($value)
        );

        if ($value === '') {
            return null;
        }

        if (!is_numeric($value) || (float) $value < 0) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function stock(?string $value): int
    {
        $value = $this->clean($value);

        if (!is_numeric($value)) {
            return 0;
        }

        return max(0, (int) $value);
    }

    private function dateValue(?string $value): ?string
    {
        $value = $this->clean($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)
                ->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
