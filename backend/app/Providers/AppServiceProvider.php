<?php

namespace App\Providers;

use App\Repositories\CategoryRepository;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * Tell Laravel: whenever someone asks for CategoryRepositoryInterface,
         * give them a CategoryRepository instance.
         *
         * This is the Dependency Inversion Principle (D in SOLID):
         * CategoryService depends on the interface, not the concrete class.
         * Swapping the database layer only requires changing this one line.
         */
        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
