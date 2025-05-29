<?php

namespace PlacetoPay\AppVersion\Tests\Mocks;

use PlacetoPay\AppVersion\Helpers\ChangelogLastChanges;
use PlacetoPay\AppVersion\Helpers\HttpClient;
use PlacetoPay\AppVersion\NewRelic\NewRelicApi;
use PlacetoPay\AppVersion\Sentry\SentryApi;

trait InteractsWithFakeClient
{
    /**
     * @var \PlacetoPay\AppVersion\Tests\Mocks\FakeSentryClient
     */
    protected HttpClient $fakeClient;

    public function bindSentryFakeClient(): void
    {
        $this->fakeClient = new FakeSentryClient();

        $fakeSentry = new SentryApi($this->fakeClient, config('app-version.sentry.auth_token'), config('app-version.sentry.organization'));

        $this->swap(SentryApi::class, $fakeSentry);
    }

    public function bindNewRelicFakeClient(?string $version = null, ?array $content = []): void
    {
        $this->fakeClient = new FakeNewRelicClient();
        $mock = $this->createPartialMock(ChangelogLastChanges::class, ['read', 'version', 'content']);
        $mock->expects($this->once())
            ->method('read');
        $mock->expects($this->once())
            ->method('version')
            ->willReturn($version);
        $mock->expects($this->once())
            ->method('content')
            ->willReturn($content);

        $fakeNewRelic = new NewRelicApi(
            $this->fakeClient,
            config('app-version.newrelic.api_key'),
            config('app-version.newrelic.entity_guid'),
            $mock
        );

        $this->swap(NewRelicApi::class, $fakeNewRelic);
    }

    /**
     * @return \PlacetoPay\AppVersion\Sentry\SentryApi
     */
    public function sentryApi()
    {
        return $this->app->make(SentryApi::class);
    }
}
