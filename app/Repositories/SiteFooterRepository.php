<?php

namespace App\Repositories;

use App\Models\SiteFooter;

class SiteFooterRepository
{
    public function get(): ?SiteFooter
    {
        return SiteFooter::query()->find(1);
    }

    public function save(array $data): SiteFooter
    {
        return SiteFooter::query()->updateOrCreate(
            ['id' => 1],
            $data
        );
    }
}
