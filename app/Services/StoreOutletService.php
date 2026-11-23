<?php

namespace App\Services;

use App\Models\StoreOutlet;
use App\Repositories\StoreOutletRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreOutletService
{
    public function __construct(
        private StoreOutletRepository $repository
    ) {}

    public function all(): array
    {
        return $this->repository->all()
            ->map(fn ($outlet) => $this->format($outlet))
            ->all();
    }

    public function published(): array
    {
        return $this->repository->published()
            ->map(fn ($outlet) => $this->format($outlet))
            ->all();
    }

    public function show(int $id): array
    {
        return $this->format(
            $this->repository->findOrFail($id)
        );
    }

    public function create(array $data): array
    {
        $outlet = DB::transaction(
            fn () => $this->repository->create($data)
        );

        return $this->format($outlet->load('images'));
    }

    public function update(int $id, array $data): array
    {
        $outlet = DB::transaction(function () use ($id, $data) {
            return $this->repository->update(
                $this->repository->findOrFail($id),
                $data
            );
        });

        return $this->format($outlet);
    }

    public function delete(int $id): void
    {
        $outlet = $this->repository->findOrFail($id);

        $paths = $outlet->images
            ->pluck('image_path')
            ->filter()
            ->all();

        DB::transaction(function () use ($outlet) {
            $this->repository->delete($outlet);
        });

        Storage::disk('public')->delete($paths);
    }

    public function addImages(int $id, array $files): array
    {
        $outlet = $this->repository->findOrFail($id);

        $existingCount = $outlet->images()->count();

        if ($existingCount + count($files) > 12) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'images' => ['An outlet can have up to 12 images.'],
            ]);
        }

        $stored = [];

        try {
            DB::transaction(function () use ($outlet, $files, &$stored) {
                $nextOrder = (int) $outlet->images()
                    ->max('display_order') + 1;

                foreach ($files as $file) {
                    if (!$file instanceof UploadedFile) {
                        continue;
                    }

                    $path = $file->store('store-outlets', 'public');
                    $stored[] = $path;

                    $outlet->images()->create([
                        'image_path' => $path,
                        'display_order' => $nextOrder++,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($stored);
            throw $exception;
        }

        return $this->show($id);
    }

    public function removeImage(int $outletId, int $imageId): array
    {
        $outlet = $this->repository->findOrFail($outletId);

        $image = $this->repository->findImageOrFail(
            $outlet,
            $imageId
        );

        $path = $image->image_path;

        DB::transaction(function () use ($image) {
            $image->delete();
        });

        Storage::disk('public')->delete($path);

        return $this->show($outletId);
    }

    private function format(StoreOutlet $outlet): array
    {
        return [
            'id' => $outlet->id,
            'name' => $outlet->name,
            'address' => $outlet->address,
            'phone' => $outlet->phone,
            'map_query' => $outlet->map_query,
            'opening_hours' => $outlet->opening_hours ?? [],
            'display_order' => $outlet->display_order,
            'is_active' => $outlet->is_active,
            'images' => $outlet->images
                ->map(fn ($image) => [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'display_order' => $image->display_order,
                ])
                ->values()
                ->all(),
        ];
    }
}
