<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Collection extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'status',
        'publish_online',
        'publish_pos',
        'collection_type',
        'sort_by',
        'display_order',
        'rule_category_id',
        'rule_brand_id',
        'rule_min_price',
        'rule_max_price',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'publish_online' => 'boolean',
        'publish_pos' => 'boolean',
        'display_order' => 'integer',
        'rule_category_id' => 'integer',
        'rule_brand_id' => 'integer',
        'rule_min_price' => 'decimal:2',
        'rule_max_price' => 'decimal:2',
    ];

    protected $appends = [
        'cover_image_url',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'collection_product',
            'collection_id',
            'product_id'
        )->withPivot('display_order');
    }

    public function ruleCategory(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'rule_category_id'
        );
    }

    public function ruleBrand(): BelongsTo
    {
        return $this->belongsTo(
            Brand::class,
            'rule_brand_id'
        );
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image) {
            return null;
        }

        return url(
            Storage::disk('public')->url($this->cover_image)
        );
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('publish_online', true);
    }
}
