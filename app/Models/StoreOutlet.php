<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreOutlet extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'map_query',
        'opening_hours',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(StoreOutletImage::class)
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
