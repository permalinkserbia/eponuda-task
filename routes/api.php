<?php

use App\Http\Controllers\Api\TelevisionController;
use App\Http\Controllers\Api\TvCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/televisions', [TelevisionController::class, 'index']);
    Route::get('/tv-categories', [TvCategoryController::class, 'index']);
    Route::get('/tv-categories/{id}/products', [TvCategoryController::class, 'products']);
});

