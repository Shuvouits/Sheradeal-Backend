<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class StorefrontCategoryRepository
{
    public function getActiveCategories(): Collection
    {
        return Category::query()
            ->where('status', 'active')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function getFeaturedCategories(): Collection
    {
        return Category::query()
            ->where('status', 'active')
            ->where('is_featured', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }
}
