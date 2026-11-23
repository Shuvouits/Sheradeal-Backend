<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $fillable = [
        'product_id',
        'media_type',
        'disk',
        'file_path',
        'external_url',
        'thumbnail_path',
        'original_filename',
        'mime_type',
        'file_size',
        'alt_text',
        'title',
        'is_cover',
        'display_order',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'file_size' => 'integer',
            'display_order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants()
    {
        return $this->hasMany(
            ProductVariant::class,
            'product_media_id'
        );
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('media_type', 'image');
    }

    public function scopeCover(Builder $query): Builder
    {
        return $query->where('is_cover', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
