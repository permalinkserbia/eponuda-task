<?php

namespace App\Repositories;

use App\Models\Television;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TelevisionRepository implements TelevisionRepositoryInterface
{
    public function paginate(int $perPage = 20, ?int $categoryId = null, ?int $page = null): LengthAwarePaginator
    {
        $query = Television::query();

        if ($categoryId) {
            $query->where('tv_category_id', $categoryId);
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return $paginator;
    }

    public function findByExternalId(string $externalId): ?Television
    {
        return Television::where('external_id', $externalId)->first();
    }

    public function create(array $data): Television
    {
        return Television::create($data);
    }

    public function update(Television $television, array $data): Television
    {
        $television->update($data);

        return $television;
    }

    public function updateOrCreate(array $attributes, array $values): Television
    {
        return Television::updateOrCreate($attributes, $values);
    }
}

