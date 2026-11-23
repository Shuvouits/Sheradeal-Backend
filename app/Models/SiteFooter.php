<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteFooter extends Model
{
    protected $fillable = [
        'brand_name',
        'brand_tagline',
        'logo_path',
        'description',
        'community_title',
        'trust_badges',
        'link_columns',
        'social_links',
        'contact',
        'payment_methods',
        'copyright_text',
        'bottom_right_text',
        'is_active',
    ];

    protected $casts = [
        'trust_badges' => 'array',
        'link_columns' => 'array',
        'social_links' => 'array',
        'contact' => 'array',
        'payment_methods' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return url(Storage::disk('public')->url($this->logo_path));
    }
}
