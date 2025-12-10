<?php

namespace App\Repositories;

use App\Models\Television;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TelevisionRepositoryInterface
{
    public function paginate(int $perPage = 20, ?int $categoryId = null): LengthAwarePaginator;

    public function findByExternalId(string $externalId): ?Television;

    public function create(array $data): Television;

    public function update(Television $television, array $data): Television;

    public function updateOrCreate(array $attributes, array $values): Television;
}

