<?php

namespace App\Repositories;

use App\Models\HomepageCategorySection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class HomepageCategorySectionRepository
{
    public function all(): EloquentCollection
    {
        return HomepageCategorySection::query()
            ->with('collection')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public function visible(): EloquentCollection
    {
        return HomepageCategorySection::query()
            ->with('collection')
            ->where('is_active', true)
            ->whereNotNull('collection_id')
            ->whereHas('collection', function ($query) {
                $query->where('status', 'active')
                    ->where('publish_online', true);
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public function findOrFail(int $id): HomepageCategorySection
    {
        return HomepageCategorySection::query()
            ->with('collection')
            ->findOrFail($id);
    }

    public function create(array $data): HomepageCategorySection
    {
        return HomepageCategorySection::create($data);
    }

    public function update(
        HomepageCategorySection $section,
        array $data
    ): HomepageCategorySection {
        $section->update($data);

        return $section->refresh()->load('collection');
    }

    public function delete(HomepageCategorySection $section): void
    {
        $section->delete();
    }
}
