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
        return $this->getImageUrl($this->desktop_image);
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        return $this->getImageUrl($this->mobile_image);
    }

    private function getImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $path = ltrim($path, '/');

        // Handle previously saved storage-prefixed paths.
        $path = preg_replace('#^(storage/|public/)#', '', $path);

        return Storage::disk('uploads')->url($path);
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
