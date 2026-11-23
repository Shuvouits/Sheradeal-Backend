<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantValue extends Model
{
    protected $fillable = [
        'product_variant_id',
        'global_variant_id',
        'global_variant_option_id',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }

    public function globalVariant(): BelongsTo
    {
        return $this->belongsTo(
            GlobalVariant::class,
            'global_variant_id'
        );
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(
            GlobalVariantOption::class,
            'global_variant_option_id'
        );
    }
}
