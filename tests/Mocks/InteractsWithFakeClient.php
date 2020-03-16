<?php

namespace PlacetoPay\AppVersion\Tests\Mocks;

use PlacetoPay\AppVersion\Sentry\SentryApi;

trait InteractsWithFakeClient
{
    /**
     * @var \PlacetoPay\AppVersion\Tests\Mocks\FakeClient
     */
    protected $fakeClient;

    public function bindFakeClient(): void
    {
        $this->fakeClient = new FakeClient();

        $fakeSentry = new SentryApi($this->fakeClient, config('app-version.sentry.organization'));

        $this->swap(SentryApi::class, $fakeSentry);
    }

    /**
     * @return \PlacetoPay\AppVersion\Sentry\SentryApi
     */
    public function sentryApi()
    {
        return $this->app->make(SentryApi::class);
    }
}
