<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalVariant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'visual_type',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(
            GlobalVariantOption::class,
            'global_variant_id'
        )->orderBy('display_order')
         ->orderBy('id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')
            ->orderBy('name');
    }

    public function productVariantValues()
{
    return $this->hasMany(
        ProductVariantValue::class,
        'global_variant_id'
    );
}


}
