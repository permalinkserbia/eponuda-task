<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelevisionIndexRequest;
use App\Http\Resources\TelevisionResource;
use App\Repositories\TelevisionRepositoryInterface;

class TelevisionController extends Controller
{
    public function __construct(
        private readonly TelevisionRepositoryInterface $televisionRepository
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(TelevisionIndexRequest $request)
    {
        $perPage = $request->validated()['per_page'] ?? 20;
        $page = $request->validated()['page'] ?? 1;
        $categoryId = $request->validated()['category_id'] ?? null;

        $televisions = $this->televisionRepository->paginate($perPage, $categoryId, $page);

        return TelevisionResource::collection($televisions);
    }
}
