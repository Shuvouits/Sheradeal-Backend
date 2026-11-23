<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalVariantOption extends Model
{
    protected $fillable = [
        'global_variant_id',
        'label',
        'value',
        'color_code',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            GlobalVariant::class,
            'global_variant_id'
        );
    }


    public function productVariantValues()
{
    return $this->hasMany(
        ProductVariantValue::class,
        'global_variant_option_id'
    );
}


}
