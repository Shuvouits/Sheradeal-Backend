<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Slider extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'desktop_image',
        'mobile_image',
        'button_text',
        'button_link',
        'text_position',
        'text_color',
        'overlay_opacity',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'overlay_opacity' => 'integer',
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $appends = [
        'desktop_image_url',
        'mobile_image_url',
    ];

    public function getDesktopImageUrlAttribute(): ?string
    {
        return $this->desktop_image
            ? Storage::disk('public')->url($this->desktop_image)
            : null;
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        return $this->mobile_image
            ? Storage::disk('public')->url($this->mobile_image)
            : null;
    }

    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }
}
