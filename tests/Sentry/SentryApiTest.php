<?php

namespace PlacetoPay\AppVersion\Tests\Sentry;

use PlacetoPay\AppVersion\Tests\Mocks\InteractsWithFakeClient;
use PlacetoPay\AppVersion\Tests\TestCase;

class SentryApiTest extends TestCase
{
    use InteractsWithFakeClient;

    public function test_it_can_create_a_sentry_release(): void
    {
        $this->setSentryEnvironmentSetUp();

        $this->bindSentryFakeClient();
        $this->fakeClient->push('success_release');

        $this->sentryApi()->createRelease(
            'aaaaab',
            'placetopay/app-version',
            'test-project'
        );

        $this->fakeClient->assertAuthenticationHeaderSent(config()->get('app-version.sentry.auth_token'));
        $this->fakeClient->assertLastRequestHas('version', 'aaaaab');
        $this->fakeClient->assertLastRequestHas('refs.0.repository', 'placetopay/app-version');
        $this->fakeClient->assertLastRequestHas('projects.0', 'test-project');
    }

    public function test_it_can_create_a_sentry_deploy(): void
    {
        $this->setSentryEnvironmentSetUp();

        $this->bindSentryFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->sentryApi()->createDeploy('version-deployed', 'local');

        $this->assertStringContainsString('releases/version-deployed/deploys/', $this->fakeClient->lastUrl());
        $this->fakeClient->assertLastRequestHas('environment', 'local');
    }
}
