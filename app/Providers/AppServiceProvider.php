<?php

namespace App\Providers;

use App\Contracts\SeoMetaManagerContract;
use App\Services\Seo\SeoMetaManager;
use App\Support\Tenant\CurrentCompany;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider encargado de resolver dependencias compartidas de la aplicación.
 *
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios de la aplicación en el contenedor de IoC.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(
            SeoMetaManagerContract::class,
            static fn ($app): SeoMetaManager => new SeoMetaManager(config('seo.defaults', []))
        );

        $this->app->singleton(CurrentCompany::class, fn (): CurrentCompany => new CurrentCompany());
    }

    /**
     * Inicializa servicios o configuraciones adicionales tras el arranque.
     *
     * @return void
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('Views/Components'));
    }
}
