<?php

namespace PlacetoPay\AppVersion\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PlacetoPay\AppVersion\VersionServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [VersionServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app-version.sentry', [
            'auth_token' => 'abcdefg',
            'organization' => 'placetopay',
            'repository' => 'app-version',
            'project' => 'test-project',
        ]);

        $app['config']->set('app-version.version', 'asdfg2');
    }
}
