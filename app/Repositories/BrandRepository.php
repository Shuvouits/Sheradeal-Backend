<?php

namespace App\Repositories;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BrandRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Brand::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $filter = $filters['filter'] ?? 'all';

        switch ($filter) {
            case 'pending':
                $query->where('approval_status', 'pending');
                break;

            case 'active':
                $query->where('status', 'active')
                    ->where('approval_status', 'approved');
                break;

            case 'featured':
                $query->where('is_featured', true);
                break;

            case 'inactive':
                $query->where('status', 'inactive');
                break;

            case 'rejected':
                $query->where('approval_status', 'rejected');
                break;

            case 'archived':
                $query->where('status', 'archived');
                break;
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $sort = $filters['sort'] ?? 'latest';

        match ($sort) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'display_order' => $query->orderBy('display_order')
                ->orderBy('name'),
            default => $query->latest(),
        };

        return $query->paginate(
            $filters['per_page'] ?? 15
        )->withQueryString();
    }

    public function statistics(): array
    {
        $counts = Brand::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE WHEN status = 'active'
                 AND approval_status = 'approved'
                 THEN 1 ELSE 0 END) as active"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'inactive'
                 THEN 1 ELSE 0 END) as inactive"
            )
            ->selectRaw(
                "SUM(CASE WHEN approval_status = 'pending'
                 THEN 1 ELSE 0 END) as pending"
            )
            ->selectRaw(
                "SUM(CASE WHEN is_featured = 1
                 THEN 1 ELSE 0 END) as featured"
            )
            ->first();

        return [
            'total' => (int) $counts->total,
            'active' => (int) $counts->active,
            'inactive' => (int) $counts->inactive,
            'pending' => (int) $counts->pending,
            'featured' => (int) $counts->featured,
        ];
    }

    public function find(int $id): Brand
    {
        return Brand::findOrFail($id);
    }

    public function findTrashed(int $id): Brand
    {
        return Brand::onlyTrashed()->findOrFail($id);
    }

    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand->refresh();
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    public function restore(Brand $brand): Brand
    {
        $brand->restore();

        return $brand->refresh();
    }

    public function slugExists(
        string $slug,
        ?int $excludeId = null
    ): bool {
        return Brand::withTrashed()
            ->where('slug', $slug)
            ->when(
                $excludeId,
                fn ($query) => $query->where('id', '!=', $excludeId)
            )
            ->exists();
    }
}
