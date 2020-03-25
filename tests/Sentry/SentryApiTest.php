<?php

namespace PlacetoPay\AppVersion\Tests\Sentry;

use PlacetoPay\AppVersion\Tests\Mocks\InteractsWithFakeClient;
use PlacetoPay\AppVersion\Tests\TestCase;

class SentryApiTest extends TestCase
{
    use InteractsWithFakeClient;

    /** @test */
    public function can_create_a_sentry_release()
    {
        $this->bindFakeClient();
        $this->fakeClient->push('success_release');

        $this->sentryApi()->createRelease(
            'aaaaab', 'placetopay/app-version', 'test-project'
        );

        $this->fakeClient->assertLastRequestHas('version', 'aaaaab');
        $this->fakeClient->assertLastRequestHas('refs.0.repository', 'placetopay/app-version');
        $this->fakeClient->assertLastRequestHas('projects.0', 'test-project');
    }

    /** @test */
    public function can_create_a_sentry_deploy()
    {
        $this->bindFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->sentryApi()->createDeploy('asdfg2', 'local');

        $this->fakeClient->assertLastRequestHas('environment', 'local');
    }
}
