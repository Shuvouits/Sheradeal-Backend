<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'title',
        'sku',
        'barcode',
        'combination_key',
        'price',
        'compare_at_price',
        'cost_per_item',
        'quantity',
        'track_quantity',
        'continue_selling_out_of_stock',
        'status',
        'display_order',
        'product_media_id',
        'weight',
        'weight_unit',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_per_item' => 'decimal:2',
            'quantity' => 'integer',
            'track_quantity' => 'boolean',
            'continue_selling_out_of_stock' => 'boolean',
            'display_order' => 'integer',
            'weight' => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(
            ProductMedia::class,
            'product_media_id'
        );
    }

    public function values(): HasMany
    {
        return $this->hasMany(
            ProductVariantValue::class,
            'product_variant_id'
        )->orderBy('id');
    }

    public function globalOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            GlobalVariantOption::class,
            'product_variant_values',
            'product_variant_id',
            'global_variant_option_id'
        )->withPivot('global_variant_id')
         ->withTimestamps();
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(
            InventoryMovement::class,
            'product_variant_id'
        )->orderByDesc('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function getAvailableForSaleAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (!$this->track_quantity) {
            return true;
        }

        return $this->quantity > 0 ||
            $this->continue_selling_out_of_stock;
    }
}
