<?php

namespace App\Services;

use App\Models\Slider;
use App\Repositories\SliderRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SliderService
{
    private const DISK = 'uploads';

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
                    $data['desktop_image'],
                    'desktop'
                );

                $uploaded[] = $data['desktop_image'];
            }

            if (isset($data['mobile_image'])) {
                $data['mobile_image'] = $this->uploadImage(
                    $data['mobile_image'],
                    'mobile'
                );

                $uploaded[] = $data['mobile_image'];
            }

            return DB::transaction(
                fn () => $this->repository->create($data)
            );
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($uploaded);

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
                if (
                    isset($data[$field]) &&
                    $data[$field] instanceof UploadedFile
                ) {
                    $oldImages[] = $slider->{$field};

                    $folder = $field === 'desktop_image'
                        ? 'desktop'
                        : 'mobile';

                    $data[$field] = $this->uploadImage(
                        $data[$field],
                        $folder
                    );

                    $uploaded[] = $data[$field];
                }
            }

            $updated = DB::transaction(
                fn () => $this->repository->update($slider, $data)
            );

            $this->deleteImages($oldImages);

            return $updated;
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($uploaded);

            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $slider = $this->repository->find($id);

        $images = [
            $slider->desktop_image,
            $slider->mobile_image,
        ];

        DB::transaction(function () use ($slider) {
            $this->repository->delete($slider);
        });

        $this->deleteImages($images);
    }

    private function uploadImage(
        UploadedFile $image,
        string $folder
    ): string {
        return $image->store(
            "sliders/{$folder}",
            self::DISK
        );
    }

    private function deleteImages(array $images): void
    {
        $disk = Storage::disk(self::DISK);

        foreach (array_filter($images) as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
