<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TelevisionResource;
use App\Http\Resources\TvCategoryResource;
use App\Repositories\TelevisionRepositoryInterface;
use App\Repositories\TvCategoryRepositoryInterface;
use Illuminate\Http\Request;

class TvCategoryController extends Controller
{
    public function __construct(
        private readonly TvCategoryRepositoryInterface $categoryRepository,
        private readonly TelevisionRepositoryInterface $televisionRepository
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = $this->categoryRepository->getSubcategories();

        return TvCategoryResource::collection($categories);
    }

    /**
     * Display products for a specific category.
     */
    public function products(Request $request, string $id)
    {
        $page = (int) $request->get('page', 1);

        $televisions = $this->televisionRepository->paginate(20, (int) $id);

        return TelevisionResource::collection($televisions);
    }
}
