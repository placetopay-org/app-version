<?php

namespace PlacetoPay\AppVersion;

use Illuminate\Support\ServiceProvider;
use PlacetoPay\AppVersion\Console\Commands\CreateDeploy;
use PlacetoPay\AppVersion\Console\Commands\CreateRelease;
use PlacetoPay\AppVersion\Console\Commands\CreateVersionFile;
use PlacetoPay\AppVersion\Http\Controllers\VersionController;

class VersionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app['router']->get('/version', ['uses' => VersionController::class . '@version', 'as' => 'app.version']);

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
    }
}
