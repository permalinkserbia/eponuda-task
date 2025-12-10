<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TvCategoryProductsRequest;
use App\Http\Resources\TelevisionResource;
use App\Http\Resources\TvCategoryResource;
use App\Repositories\TelevisionRepositoryInterface;
use App\Repositories\TvCategoryRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
    public function products(TvCategoryProductsRequest $request, string $id)
    {
        $categoryId = (int) $id;
        
        // Validate category exists
        if (!$this->categoryRepository->exists($categoryId)) {
            abort(404, 'Category not found');
        }

        $perPage = $request->validated()['per_page'] ?? 20;
        $page = $request->validated()['page'] ?? 1;

        $televisions = $this->televisionRepository->paginate($perPage, $categoryId, $page);

        return TelevisionResource::collection($televisions);
    }
}
