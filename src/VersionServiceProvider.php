<?php

namespace PlacetoPay\AppVersion;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use PlacetoPay\AppVersion\Console\Commands\CreateDeploy;
use PlacetoPay\AppVersion\Console\Commands\CreateRelease;
use PlacetoPay\AppVersion\Console\Commands\CreateVersionFile;
use PlacetoPay\AppVersion\Http\Controllers\VersionController;
use PlacetoPay\AppVersion\Sentry\SentryApi;

class VersionServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['router']->get('/version', ['uses' => VersionController::class.'@version', 'as' => 'app.version']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateVersionFile::class,
                CreateRelease::class,
                CreateDeploy::class,
            ]);
        }

        $this->app->singleton(SentryApi::class, function (Application $app) {
            return SentryApi::create(
                $app['config']->get('app-version.sentry.auth_token'),
                $app['config']->get('app-version.sentry.organization')
            );
        });

        $this->publishes([
            __DIR__.'/../config/app-version.php' => config_path('app-version.php'),
        ]);
    }
}
