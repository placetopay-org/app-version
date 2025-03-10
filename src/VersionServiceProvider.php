<?php

namespace PlacetoPay\AppVersion;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use PlacetoPay\AppVersion\Console\Commands\CreateDeploy;
use PlacetoPay\AppVersion\Console\Commands\CreateRelease;
use PlacetoPay\AppVersion\Console\Commands\CreateVersionFile;
use PlacetoPay\AppVersion\Http\Controllers\VersionController;
use PlacetoPay\AppVersion\NewRelic\NewRelicApi;
use PlacetoPay\AppVersion\Sentry\SentryApi;

class VersionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app['router']->get('/version', [
            'uses' => VersionController::class . '@version',
            'as' => 'app.version',
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateVersionFile::class,
                CreateRelease::class,
                CreateDeploy::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/app-version.php' => config_path('app-version.php'),
        ]);

        $this->mergeConfigFrom(__DIR__ . '/../config/app-version.php', 'app-version');
    }

    public function register()
    {
        if (config()->get('app-version.sentry.auth_token')) {
            $this->app->singleton(SentryApi::class, function (Application $app) {
                return SentryApi::create(
                    $app['config']->get('app-version.sentry.auth_token'),
                    $app['config']->get('app-version.sentry.organization')
                );
            });
        }

        if (config()->get('app-version.newrelic.api_key')) {
            $this->app->singleton(NewRelicApi::class, function (Application $app) {
                return NewRelicApi::create(
                    $app['config']->get('app-version.newrelic.api_key'),
                    $app['config']->get('app-version.newrelic.application_id'),
                    $app['config']->get('app-version.newrelic.entity_guid')
                );
            });
        }
    }
}
