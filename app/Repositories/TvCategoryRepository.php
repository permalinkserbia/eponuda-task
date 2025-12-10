<?php

namespace App\Repositories;

use App\Models\TvCategory;
use Illuminate\Support\Collection;

class TvCategoryRepository implements TvCategoryRepositoryInterface
{
    public function all(): Collection
    {
        return TvCategory::all();
    }

    public function findBySlug(string $slug): ?TvCategory
    {
        return TvCategory::where('slug', $slug)->first();
    }

    public function findByUrl(string $url): ?TvCategory
    {
        return TvCategory::where('url', $url)->first();
    }

    public function create(array $data): TvCategory
    {
        return TvCategory::create($data);
    }

    public function updateOrCreate(array $attributes, array $values): TvCategory
    {
        return TvCategory::updateOrCreate($attributes, $values);
    }

    public function getSubcategories(?int $parentId = null): Collection
    {
        return TvCategory::where('parent_id', $parentId)->get();
    }

    public function exists(int $id): bool
    {
        return TvCategory::where('id', $id)->exists();
    }
}

