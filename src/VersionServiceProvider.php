<?php

namespace PlacetoPay\AppVersion;

use Illuminate\Support\ServiceProvider;
use PlacetoPay\AppVersion\Http\Controllers\VersionController;

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
        $this->app['router']->get('/version', ['uses' => VersionController::class . '@version', 'as' => 'app.version']);
    }

}
