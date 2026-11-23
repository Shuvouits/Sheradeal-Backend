<?php

namespace App\Repositories;

use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AdvertisementRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Advertisement::query()
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Advertisement
    {
        return Advertisement::query()->findOrFail($id);
    }

    public function create(array $data): Advertisement
    {
        return Advertisement::create($data);
    }

    public function update(
        Advertisement $advertisement,
        array $data
    ): Advertisement {
        $advertisement->update($data);

        return $advertisement->refresh();
    }

    public function delete(Advertisement $advertisement): void
    {
        $advertisement->delete();
    }

    public function visible(int $limit = 12): Collection
    {
        return Advertisement::query()
            ->visible()
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
