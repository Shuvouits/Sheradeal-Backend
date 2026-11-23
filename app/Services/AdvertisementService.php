<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Repositories\AdvertisementRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdvertisementService
{
    private const ADMIN_TIMEZONE = 'Asia/Dhaka';

    public function __construct(
        private AdvertisementRepository $repository
    ) {}

    public function list(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    public function find(int $id): Advertisement
    {
        return $this->repository->findOrFail($id);
    }

    public function visible(int $limit = 12)
    {
        return $this->repository->visible($limit);
    }

    public function create(array $data): Advertisement
    {
        $uploadedImages = [];

        $data = $this->normalizeSchedule($data);

        try {
            foreach (['desktop_image', 'mobile_image'] as $field) {
                if (
                    isset($data[$field]) &&
                    $data[$field] instanceof UploadedFile
                ) {
                    $path = $this->upload($data[$field]);

                    $data[$field] = $path;
                    $uploadedImages[] = $path;
                }
            }

            return DB::transaction(
                fn () => $this->repository->create($data)
            );
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($uploadedImages);

            throw $exception;
        }
    }

    public function update(int $id, array $data): Advertisement
    {
        $advertisement = $this->repository->findOrFail($id);

        $uploadedImages = [];
        $oldImages = [];

        $data = $this->normalizeSchedule($data);

        try {
            foreach (['desktop_image', 'mobile_image'] as $field) {
                if (
                    isset($data[$field]) &&
                    $data[$field] instanceof UploadedFile
                ) {
                    if ($advertisement->{$field}) {
                        $oldImages[] = $advertisement->{$field};
                    }

                    $path = $this->upload($data[$field]);

                    $data[$field] = $path;
                    $uploadedImages[] = $path;
                }
            }

            $updated = DB::transaction(
                fn () => $this->repository->update(
                    $advertisement,
                    $data
                )
            );

            Storage::disk('public')->delete($oldImages);

            return $updated;
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($uploadedImages);

            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $advertisement = $this->repository->findOrFail($id);

        $images = array_filter([
            $advertisement->desktop_image,
            $advertisement->mobile_image,
        ]);

        DB::transaction(function () use ($advertisement) {
            $this->repository->delete($advertisement);
        });

        Storage::disk('public')->delete($images);
    }

    private function upload(UploadedFile $image): string
    {
        return $image->store('advertisements', 'public');
    }

    private function normalizeSchedule(array $data): array
    {
        foreach (['starts_at', 'ends_at'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if (
                $data[$field] === null ||
                trim((string) $data[$field]) === ''
            ) {
                $data[$field] = null;
                continue;
            }

            $data[$field] = Carbon::parse(
                $data[$field],
                self::ADMIN_TIMEZONE
            )
                ->utc()
                ->format('Y-m-d H:i:s');
        }

        return $data;
    }
}
