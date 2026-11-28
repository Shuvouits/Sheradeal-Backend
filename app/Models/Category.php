<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'cover_image',
        'icon',
        'status',
        'is_featured',
        'display_order',
        'tax_class',
        'meta_title',
        'meta_description',
        'tags',
        'created_by',
        'updated_by',
        'show_in_megamenu',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_featured' => 'boolean',
            'display_order' => 'integer',
            'tags' => 'array',
            'show_in_megamenu' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            Category::class,
            'parent_id'
        )->orderBy('display_order')
         ->orderBy('name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function products()
{
    return $this->hasMany(Product::class, 'category_id');
}


}
