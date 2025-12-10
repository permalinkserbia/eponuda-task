<?php

namespace App\Repositories;

use App\Models\TvCategory;
use Illuminate\Support\Collection;

interface TvCategoryRepositoryInterface
{
    public function all(): Collection;

    public function findBySlug(string $slug): ?TvCategory;

    public function findByUrl(string $url): ?TvCategory;

    public function create(array $data): TvCategory;

    public function updateOrCreate(array $attributes, array $values): TvCategory;

    public function getSubcategories(?int $parentId = null): Collection;

    public function exists(int $id): bool;
}

