<?php

namespace App\Services;

use App\Models\Slider;
use App\Repositories\SliderRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SliderService
{
    public function __construct(
        private SliderRepository $repository
    ) {}

    public function list(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    public function find(int $id): Slider
    {
        return $this->repository->find($id);
    }

    public function visible()
    {
        return $this->repository->visible();
    }

    public function create(array $data): Slider
    {
        $uploaded = [];

        try {
            if (isset($data['desktop_image'])) {
                $data['desktop_image'] = $this->uploadImage(
                    $data['desktop_image']
                );
                $uploaded[] = $data['desktop_image'];
            }

            if (isset($data['mobile_image'])) {
                $data['mobile_image'] = $this->uploadImage(
                    $data['mobile_image']
                );
                $uploaded[] = $data['mobile_image'];
            }

            return DB::transaction(
                fn () => $this->repository->create($data)
            );
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($uploaded);
            throw $exception;
        }
    }

    public function update(int $id, array $data): Slider
    {
        $slider = $this->repository->find($id);
        $uploaded = [];
        $oldImages = [];

        try {
            foreach (['desktop_image', 'mobile_image'] as $field) {
                if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
                    $oldImages[] = $slider->{$field};
                    $data[$field] = $this->uploadImage($data[$field]);
                    $uploaded[] = $data[$field];
                }
            }

            $updated = DB::transaction(
                fn () => $this->repository->update($slider, $data)
            );

            Storage::disk('public')->delete(
                array_filter($oldImages)
            );

            return $updated;
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($uploaded);
            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $slider = $this->repository->find($id);

        $images = array_filter([
            $slider->desktop_image,
            $slider->mobile_image,
        ]);

        DB::transaction(function () use ($slider) {
            $this->repository->delete($slider);
        });

        Storage::disk('public')->delete($images);
    }

    private function uploadImage(UploadedFile $image): string
    {
        return $image->store('sliders', 'public');
    }
}
