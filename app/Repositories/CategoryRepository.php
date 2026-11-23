<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CategoryRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Category::query()->with('parent:id,name,slug');

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        switch ($filters['filter'] ?? 'all') {
            case 'active':
                $query->where('status', 'active');
                break;

            case 'inactive':
                $query->where('status', 'inactive');
                break;

            case 'featured':
                $query->where('is_featured', true);
                break;
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        switch ($filters['sort'] ?? 'name_asc') {
            case 'latest':
                $query->latest();
                break;

            case 'oldest':
                $query->oldest();
                break;

            case 'name_desc':
                $query->orderByDesc('name');
                break;

            case 'display_order':
                $query->orderBy('display_order')->orderBy('name');
                break;

            default:
                $query->orderBy('name');
                break;
        }

        return $query->paginate(
            (int) ($filters['per_page'] ?? 15)
        )->withQueryString();
    }

    public function statistics(): array
    {
        return [
            'total' => Category::count(),
            'active' => Category::where('status', 'active')->count(),
            'inactive' => Category::where('status', 'inactive')->count(),
            'parent_categories' => Category::whereNull('parent_id')->count(),
            'assigned_products' => null,
        ];
    }

    public function tree(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->with([
                'children' => fn ($query) => $query->with('children'),
            ])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): Category
    {
        return Category::with('parent:id,name,slug')
            ->findOrFail($id);
    }

    public function findTrashed(int $id): Category
    {
        return Category::onlyTrashed()->findOrFail($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function restore(Category $category): Category
    {
        $category->restore();

        return $category->refresh();
    }

    public function hasChildren(int $id): bool
    {
        return Category::where('parent_id', $id)->exists();
    }

    public function slugExists(
        string $slug,
        ?int $excludeId = null
    ): bool {
        return Category::withTrashed()
            ->where('slug', $slug)
            ->when(
                $excludeId !== null,
                fn ($query) => $query->where('id', '!=', $excludeId)
            )
            ->exists();
    }
}
