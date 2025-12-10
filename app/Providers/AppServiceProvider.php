<?php

namespace App\Providers;

use App\Repositories\TelevisionRepository;
use App\Repositories\TelevisionRepositoryInterface;
use App\Repositories\TvCategoryRepository;
use App\Repositories\TvCategoryRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TelevisionRepositoryInterface::class, TelevisionRepository::class);
        $this->app->bind(TvCategoryRepositoryInterface::class, TvCategoryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
