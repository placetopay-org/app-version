<?php

namespace PlacetoPay\AppVersion\Tests\Commands;

use PlacetoPay\AppVersion\Helpers\Changelog;
use PlacetoPay\AppVersion\Tests\Mocks\InteractsWithFakeClient;
use PlacetoPay\AppVersion\Tests\TestCase;

class CreateDeployCommandTest extends TestCase
{
    use InteractsWithFakeClient;

    /** @test */
    public function can_create_a_release_for_sentry()
    {
        $this->setSentryEnvironmentSetUp();

        $this->bindSentryFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('environment', 'testing');

        $this->assertStringContainsString('Authorization: Bearer', $this->fakeClient->lastRequest()['headers'][0]);
    }

    /** @test */
    public function can_create_a_release_for_newrelic()
    {
        $this->setNewRelicEnvironmentSetUp();

        $this->bindNewRelicFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('deployment', [
            'revision' => 'asdfg2',
            'changelog' => 'Not available right now',
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]);

        $this->assertEquals($this->fakeClient->lastRequest()['headers'][0], 'X-Api-Key: ' . config('app-version.newrelic.api_key'));
    }

    public function test_create_newrelic_deploy_with_changelog(): void
    {
        file_put_contents(Changelog::path(), 'TEST');

        $this->setNewRelicEnvironmentSetUp();
        $this->bindNewRelicFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('deployment', [
            'revision' => 'asdfg2',
            'changelog' => 'TEST',
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]);

        $this->assertEquals(
            $this->fakeClient->lastRequest()['headers'][0],
            'X-Api-Key: ' . config('app-version.newrelic.api_key')
        );

        unlink(Changelog::path());
    }
}
