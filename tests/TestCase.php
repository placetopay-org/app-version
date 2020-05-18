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

    protected function setSentryEnvironmentSetUp()
    {
        config()->set('app-version.sentry', [
            'auth_token' => 'abcdefg',
            'organization' => 'placetopay',
            'repository' => 'app-version',
            'project' => 'test-project',
        ]);

        config()->set('app-version.version', 'asdfg2');
    }

    protected function setNewRelicEnvironmentSetUp()
    {
        config()->set('app-version.newrelic', [
            'api_key' => 'abcdefg',
            'application_id' => 'placetopay',
        ]);

        config()->set('app-version.version', 'asdfg2');
    }
}
