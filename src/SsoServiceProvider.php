<?php

namespace Mds\SsoClient;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class SsoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publicar archivo de configuración
        $this->publishes([
            __DIR__.'/../config/sso.php' => config_path('sso.php'),
        ], 'sso-config');

        // Registrar alias automáticamente en Laravel 12
        $this->registerAlias();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Fusionar configuración
        $this->mergeConfigFrom(
            __DIR__.'/../config/sso.php',
            'sso'
        );

        // Registrar singleton
        $this->app->singleton('sso', function ($app) {
            return new Sso();
        });
    }

    /**
     * Registrar alias automáticamente
     *
     * @return void
     */
    protected function registerAlias()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Sso', \Mds\SsoClient\Facades\Sso::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['sso'];
    }
} 