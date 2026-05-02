<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View as Facade;
use App\View\Composers\LanguageComposer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register view composer for language data
        View::composer('components.layouts.app.sidebar', LanguageComposer::class);

        // Register app.sidebar component alias
        Blade::component('components.layouts.app.sidebar', 'app.sidebar');

        // Add layouts view namespace so layouts::app works
        Facade::addNamespace('layouts', resource_path('views/components/layouts'));
    }
}
