<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Blade;

class VoltServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $viewPath = config('livewire.view_path', resource_path('views/livewire'));

        Volt::mount([
            $viewPath,
            resource_path('views/pages'),
        ]);

        // Register layouts component namespace
        Blade::componentNamespace('App\\View\\Components\\Layouts', 'layouts');
    }
}
