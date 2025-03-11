<?php

namespace PlacetoPay\AppVersion\Tests\Commands;

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

        $this->fakeClient->assertLastRequestHas('query', <<<GRAPHQL
        mutation {
          changeTrackingCreateDeployment(
            deployment: {
              version: "asdfg2",
              entityGuid: "",
              changelog: "Not available right now"
              description: "Commit on testing",
              user: "Not available right now",
            }
          ) {
            deploymentId
            entityGuid
            changelog
            description
            version
            timestamp
            user
          }
        }
        GRAPHQL);

        $this->assertEquals($this->fakeClient->lastRequest()['headers'][0], 'API-Key: ' . config('app-version.newrelic.api_key'));
    }
}
