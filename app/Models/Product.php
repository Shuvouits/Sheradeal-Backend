<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
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
        'published_at',

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

        'has_variants',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'additional_attributes' => 'array',

            'is_featured' => 'boolean',
            'publish_online' => 'boolean',
            'publish_pos' => 'boolean',
            'published_at' => 'datetime',

            'is_final_sale' => 'boolean',
            'cod_advance_required' => 'boolean',

            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_per_item' => 'decimal:2',
            'unit_price' => 'decimal:4',
            'special_price' => 'decimal:2',

            'cod_advance_amount' => 'decimal:2',

            'special_price_from' => 'datetime',
            'special_price_to' => 'datetime',

            'quantity' => 'integer',
            'track_quantity' => 'boolean',
            'continue_selling_out_of_stock' => 'boolean',

            'min_order_quantity' => 'integer',
            'quantity_multiple' => 'integer',
            'max_order_quantity' => 'integer',

            'requires_shipping' => 'boolean',
            'weight' => 'decimal:3',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',

            'has_variants' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Organization
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Media
    |--------------------------------------------------------------------------
    */

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function coverImage(): HasMany
    {
        return $this->hasMany(ProductMedia::class)
            ->where('is_cover', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Variants
    |--------------------------------------------------------------------------
    */

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('display_order')
            ->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)
            ->orderByDesc('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePublishedOnline($query)
    {
        return $query
            ->where('status', 'active')
            ->where('publish_online', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($q) use ($search) {
            $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        });
    }
}
