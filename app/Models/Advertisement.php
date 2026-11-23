<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Advertisement extends Model
{
    protected $fillable = [
        'name',
        'badge_text',
        'heading',
        'description',
        'desktop_image',
        'mobile_image',
        'background_color',
        'text_color',
        'primary_button_text',
        'primary_button_link',
        'secondary_button_text',
        'secondary_button_link',
        'display_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
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
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
}
