<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StoreOutletImage extends Model
{
    protected $fillable = [
        'store_outlet_id',
        'image_path',
        'display_order',
    ];

    protected $appends = ['image_url'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(
            StoreOutlet::class,
            'store_outlet_id'
        );
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return url(Storage::disk('public')->url($this->image_path));
    }
}
