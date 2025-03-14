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
            timestamp
          }
        }
        GRAPHQL);

        $this->assertEquals($this->fakeClient->lastRequest()['headers'][0], 'API-Key: ' . config('app-version.newrelic.api_key'));
    }

    /** @test */
    public function can_not_create_a_release_if_has_invalid_version_data()
    {

        config()->set('app-version.version.sha', '');

        $this
            ->artisan('app-version:create-deploy')
            ->expectsOutput(
                "General configuration is not valid:\nThe version.sha field is required."
            );
    }

    /** @test */
    public function can_not_create_a_release_if_has_invalid_data()
    {
        config()->set('app-version.version.sha', 'asdfg2');

        config()->set('app-version.newrelic', [
            'api_key' => '',
            'entity_guid' => '',
        ]);

        config()->set('app-version.sentry', [
            'auth_token' => '',
            'organization' => '',
        ]);

        $this
            ->artisan('app-version:create-deploy')
            ->assertSuccessful()
            ->expectsOutput("Sentry configuration is not valid:\nThe sentry.auth token field is required.\nThe sentry.organization field is required.")
            ->expectsOutput("Newrelic configuration is not valid:\nThe newrelic.api key field is required.\nThe newrelic.entity guid field is required.")
            ->doesntExpectOutput("General configuration is not valid:\nThe version.sha field is required.");
    }

}
