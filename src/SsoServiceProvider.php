<?php

namespace Mds\SsoClient;

use Illuminate\Support\ServiceProvider;

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
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['sso'];
    }
} 