<?php

namespace PlacetoPay\AppVersion\Tests\Sentry;

use Orchestra\Testbench\TestCase;
use PlacetoPay\AppVersion\Sentry\SentryApi;
use PlacetoPay\AppVersion\Tests\Mocks\FakeClient;

class CreateReleaseTest extends TestCase
{
    /**
     * @var \PlacetoPay\AppVersion\Sentry\SentryApi
     */
    private $sentry;

    /**
     * @var \PlacetoPay\AppVersion\Tests\Mocks\FakeClient
     */
    private $fakeClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeClient = new FakeClient();

        $this->sentry = new SentryApi(
            $this->fakeClient,
            'organization'
        );
    }

    /** @test **/
    public function can_create_a_sentry_release()
    {
        $this->fakeClient->push('success_release');

        $this->sentry->createRelease(
            'aaaaab', 'placetopay/app-version', 'test-project'
        );

        $this->fakeClient->assertLastRequestHas('version', 'aaaaab');
        $this->fakeClient->assertLastRequestHas('refs.0.repository', 'placetopay/app-version');
        $this->fakeClient->assertLastRequestHas('projects.0', 'test-project');
    }

    /** @test **/
    public function can_create_a_sentry_deploy()
    {
        $this->fakeClient->push('success_deploy');

        $this->sentry->createDeploy('aaaaab', 'local',);

        $this->fakeClient->assertLastRequestHas('environment', 'local');
    }
}
