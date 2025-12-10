<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TelevisionResource;
use App\Repositories\TelevisionRepositoryInterface;
use Illuminate\Http\Request;

class TelevisionController extends Controller
{
    public function __construct(
        private readonly TelevisionRepositoryInterface $televisionRepository
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $categoryId = $request->get('category_id') ? (int) $request->get('category_id') : null;

        $televisions = $this->televisionRepository->paginate(20, $categoryId);

        return TelevisionResource::collection($televisions);
    }
}
