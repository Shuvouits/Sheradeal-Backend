<?php

namespace App\Services;

use App\Models\GlobalVariant;
use App\Repositories\GlobalVariantRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GlobalVariantService
{
    public function __construct(
        protected GlobalVariantRepository $repository
    ) {}

    public function list(?string $search = null)
    {
        return $this->repository->all($search);
    }

    public function find(int $id): GlobalVariant
    {
        return $this->repository->find($id);
    }

    public function create(array $data): GlobalVariant
    {
        return DB::transaction(function () use ($data) {
            $variant = $this->repository->create([
                'name' => trim($data['name']),
                'slug' => $this->generateSlug(
                    $data['slug'] ?? $data['name']
                ),
                'visual_type' => $data['visual_type'],
                'display_order' => $data['display_order'] ?? 0,
            ]);

            $this->syncOptions(
                $variant,
                $data['options'],
                $data['visual_type'],
                false
            );

            return $this->repository->find($variant->id);
        });
    }

    public function update(
        int $id,
        array $data
    ): GlobalVariant {
        return DB::transaction(function () use ($id, $data) {
            $variant = $this->repository->findForUpdate($id);

            $this->repository->update($variant, [
                'name' => trim($data['name']),
                'slug' => $this->generateSlug(
                    $data['slug'] ?? $data['name'],
                    $variant->id
                ),
                'visual_type' => $data['visual_type'],
                'display_order' => $data['display_order']
                    ?? $variant->display_order,
            ]);

            $this->syncOptions(
                $variant,
                $data['options'],
                $data['visual_type'],
                true
            );

            return $this->repository->find($variant->id);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $variant = $this->repository->findForUpdate($id);

            // Product variant reference protection must be added
            // when the Product Variant tables are introduced.
            $this->repository->delete($variant);
        });
    }

    public function reorderOptions(
        int $id,
        array $optionIds
    ): GlobalVariant {
        return DB::transaction(function () use ($id, $optionIds) {
            $variant = $this->repository->findForUpdate($id);

            $existingIds = $variant->options()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $submittedIds = collect($optionIds)
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($existingIds !== $submittedIds) {
                throw ValidationException::withMessages([
                    'option_ids' => [
                        'Provide every existing option ID exactly once.',
                    ],
                ]);
            }

            foreach ($optionIds as $index => $optionId) {
                $variant->options()
                    ->whereKey($optionId)
                    ->update([
                        'display_order' => $index + 1,
                    ]);
            }

            return $this->repository->find($id);
        });
    }

    private function syncOptions(
        GlobalVariant $variant,
        array $options,
        string $visualType,
        bool $isUpdate
    ): void {
        $prepared = [];
        $values = [];

        foreach ($options as $index => $option) {
            $label = trim($option['label']);

            $value = Str::slug(
                $option['value'] ?? $label
            );

            if ($value === '') {
                throw ValidationException::withMessages([
                    "options.$index.value" => [
                        'Option value cannot be empty.',
                    ],
                ]);
            }

            if (strlen($value) > 180) {
                throw ValidationException::withMessages([
                    "options.$index.value" => [
                        'Option value is too long.',
                    ],
                ]);
            }

            if (in_array($value, $values, true)) {
                throw ValidationException::withMessages([
                    "options.$index.label" => [
                        'Duplicate option value is not allowed.',
                    ],
                ]);
            }

            $values[] = $value;

            $color = $option['color_code'] ?? null;

            if ($visualType === 'color') {
                if (!$color || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                    throw ValidationException::withMessages([
                        "options.$index.color_code" => [
                            'Each color option requires a valid HEX color.',
                        ],
                    ]);
                }

                $color = strtoupper($color);
            } else {
                $color = null;
            }

            $optionId = isset($option['id'])
                ? (int) $option['id']
                : null;

            if ($optionId && !$isUpdate) {
                throw ValidationException::withMessages([
                    "options.$index.id" => [
                        'New variants cannot contain existing option IDs.',
                    ],
                ]);
            }

            if (
                $optionId &&
                !$this->repository->optionBelongsToVariant(
                    $optionId,
                    $variant->id
                )
            ) {
                throw ValidationException::withMessages([
                    "options.$index.id" => [
                        'This option does not belong to the variant.',
                    ],
                ]);
            }

            $prepared[] = [
                'id' => $optionId,
                'label' => $label,
                'value' => $value,
                'color_code' => $color,
                'display_order' => $index + 1,
            ];
        }

        $keepIds = array_values(array_filter(
            array_column($prepared, 'id'),
            fn ($id) => $id !== null
        ));

        // Delete removed options before inserting new values.
        $this->repository->deleteOptionsExcept(
            $variant,
            $keepIds
        );

        foreach ($prepared as $option) {
            $optionId = $option['id'];
            unset($option['id']);

            if ($optionId) {
                $existing = $variant->options()
                    ->whereKey($optionId)
                    ->firstOrFail();

                $this->repository->updateOption(
                    $existing,
                    $option
                );
            } else {
                $this->repository->createOption(
                    $variant,
                    $option
                );
            }
        }
    }

    private function generateSlug(
        string $name,
        ?int $excludeId = null
    ): string {
        $base = Str::slug($name);

        if ($base === '') {
            throw ValidationException::withMessages([
                'slug' => ['Enter a valid variant name.'],
            ]);
        }

        $base = substr($base, 0, 165);
        $slug = $base;
        $counter = 2;

        while ($this->repository->slugExists($slug, $excludeId)) {
            $suffix = '-' . $counter;

            $slug = substr(
                $base,
                0,
                180 - strlen($suffix)
            ) . $suffix;

            $counter++;
        }

        return $slug;
    }
}
