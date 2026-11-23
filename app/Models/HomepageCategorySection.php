<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageCategorySection extends Model
{
    protected $fillable = [
        'category_id',
        'collection_id',
        'title',
        'selection_mode',
        'product_limit',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'collection_id' => 'integer',
        'product_limit' => 'integer',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
