<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\StorefrontCategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class StorefrontCategoryService
{
    public function __construct(
        private StorefrontCategoryRepository $repository
    ) {}

    public function getCategories(bool $featuredOnly = false): array
    {
        $categories = $featuredOnly
            ? $this->repository->getFeaturedCategories()
            : $this->repository->getActiveCategories();

        return $this->formatCategories($categories);
    }

    private function formatCategories(Collection $categories): array
    {
        return $categories
            ->map(fn (Category $category) => $this->formatCategory($category))
            ->values()
            ->all();
    }

    private function formatCategory(Category $category): array
    {
        $image = $category->cover_image ?: $category->icon;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,

            'image' => $image,

            'image_url' => $image
                ? Storage::disk('public')->url($image)
                : null,

            'is_active' => $category->status === 'active',
            'is_featured' => (bool) $category->is_featured,

            'sort_order' => (int) $category->display_order,
        ];
    }
}
