<?php

namespace App\Repositories;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SliderRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Slider::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function find(int $id): Slider
    {
        return Slider::findOrFail($id);
    }

    public function create(array $data): Slider
    {
        return Slider::create($data);
    }

    public function update(Slider $slider, array $data): Slider
    {
        $slider->update($data);

        return $slider->refresh();
    }

    public function delete(Slider $slider): void
    {
        $slider->delete();
    }

    public function visible(): Collection
    {
        return Slider::query()
            ->visible()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }
}
