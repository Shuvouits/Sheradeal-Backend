<?php

namespace App\Repositories;

use App\Models\StoreOutlet;
use App\Models\StoreOutletImage;
use Illuminate\Database\Eloquent\Collection;

class StoreOutletRepository
{
    public function all(): Collection
    {
        return StoreOutlet::query()
            ->with('images')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public function published(): Collection
    {
        return StoreOutlet::query()
            ->with('images')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public function findOrFail(int $id): StoreOutlet
    {
        return StoreOutlet::query()
            ->with('images')
            ->findOrFail($id);
    }

    public function create(array $data): StoreOutlet
    {
        return StoreOutlet::create($data);
    }

    public function update(StoreOutlet $outlet, array $data): StoreOutlet
    {
        $outlet->update($data);

        return $outlet->refresh()->load('images');
    }

    public function delete(StoreOutlet $outlet): void
    {
        $outlet->delete();
    }

    public function findImageOrFail(
        StoreOutlet $outlet,
        int $imageId
    ): StoreOutletImage {
        return $outlet->images()
            ->whereKey($imageId)
            ->firstOrFail();
    }
}
