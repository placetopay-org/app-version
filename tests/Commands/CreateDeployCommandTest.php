<?php

namespace PlacetoPay\AppVersion\Tests\Commands;

use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Helpers\ChangelogLastChanges;
use PlacetoPay\AppVersion\NewRelic\NewRelicApi;
use PlacetoPay\AppVersion\Tests\Mocks\FakeNewRelicClient;
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

        $this->artisan('app-version:create-deploy')
            ->assertSuccessful()
            ->expectsOutput('SENTRY deployment created successfully');

        $this->fakeClient->assertLastRequestHas('environment', 'testing');

        $this->assertStringContainsString('Authorization: Bearer', $this->fakeClient->lastRequest()['headers'][0]);
    }

    /** @test */
    public function can_create_a_release_for_newrelic()
    {
        $this->setNewRelicEnvironmentSetUp();

        $version = '1.1.0';
        $changes = [
            'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12389)',
            'Change [868c4frhp](https://app.clickup.com/t/868c4frhp)',
            'Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12345)',
        ];
        $this->bindNewRelicFakeClient($version, $changes);

        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')
            ->assertSuccessful()
            ->expectsOutput('NEWRELIC deployment created successfully');

        $this->fakeClient->assertLastRequestHas('query', <<<'GRAPHQL'
mutation ($deployment: DeploymentInput!) {
  changeTrackingCreateDeployment(deployment: $deployment) {
    deploymentId
    timestamp
  }
}
GRAPHQL);

        $this->fakeClient->assertLastRequestHas('variables', ['deployment' => [
            'version' => 'asdfg2',
            'entityGuid' => 'placetopay',
            'changelog' => json_encode(['version' => $version, 'content' => $changes]),
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]]);

        $this->assertEquals($this->fakeClient->lastRequest()['headers'][0], 'API-Key: ' . config('app-version.newrelic.api_key'));
    }

    /** @test */
    public function can_create_a_release_for_newrelic_if_fail_to_get_changelog_data()
    {
        $this->setNewRelicEnvironmentSetUp();

        $fakeClient = new FakeNewRelicClient();
        $mock = $this->createPartialMock(ChangelogLastChanges::class, ['read', 'version', 'content']);
        $mock->expects($this->once())
            ->method('read')
            ->willThrowException(ChangelogException::forNoPermissionsToReadTheFile('changelog.md'));

        $fakeNewRelic = new NewRelicApi(
            $fakeClient,
            config('app-version.newrelic.api_key'),
            config('app-version.newrelic.entity_guid'),
            $mock
        );

        $this->swap(NewRelicApi::class, $fakeNewRelic);
        $fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')
            ->assertSuccessful()
            ->expectsOutput('NEWRELIC deployment created successfully');

        $fakeClient->assertLastRequestHas('query', <<<'GRAPHQL'
mutation ($deployment: DeploymentInput!) {
  changeTrackingCreateDeployment(deployment: $deployment) {
    deploymentId
    timestamp
  }
}
GRAPHQL);

        $fakeClient->assertLastRequestHas('variables', ['deployment' => [
            'version' => 'asdfg2',
            'entityGuid' => 'placetopay',
            'changelog' => '',
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]]);

        $this->assertEquals($fakeClient->lastRequest()['headers'][0], 'API-Key: ' . config('app-version.newrelic.api_key'));
    }

    /** @test */
    public function can_create_a_release_for_newrelic_with_subtitles()
    {
        $this->setNewRelicEnvironmentSetUp();

        $version = '1.1.0';
        $this->bindNewRelicFakeClient($version, [
            'feature',
            'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12345)',
            'Refactor',
            'Refactor',
            'Change (https://app.clickup.com/t/789/CU-12389)',
            'Bugfix',
            'Change [868c4frhp](https://app.clickup.com/t/868c4frhp)',
            'BREAKING CHANGES',
            'Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'dependencies',
            'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12345)',
        ]);

        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')
            ->assertSuccessful()
            ->expectsOutput('NEWRELIC deployment created successfully');

        $this->fakeClient->assertLastRequestHas('query', <<<'GRAPHQL'
mutation ($deployment: DeploymentInput!) {
  changeTrackingCreateDeployment(deployment: $deployment) {
    deploymentId
    timestamp
  }
}
GRAPHQL);

        $this->fakeClient->assertLastRequestHas('variables', ['deployment' => [
            'version' => 'asdfg2',
            'entityGuid' => 'placetopay',
            'changelog' => json_encode(['version' => $version, 'content' => [
                'feature' => [
                    'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
                    'Change (https://app.clickup.com/t/789/CU-12345)',
                ],
                'refactor' => [
                    'Change (https://app.clickup.com/t/789/CU-12389)',
                    ],
                'bugfix' => [
                    'Change [868c4frhp](https://app.clickup.com/t/868c4frhp)',
                    ],
                'breaking changes' => [
                    'Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)',
                    ],
                'dependencies' => [
                    'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
                    'Change (https://app.clickup.com/t/789/CU-12345)',
                ],
            ]]),
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]]);

        $this->assertEquals($this->fakeClient->lastRequest()['headers'][0], 'API-Key: ' . config('app-version.newrelic.api_key'));
    }

    /** @test */
    public function can_not_create_a_release_for_newrelic_if_query_has_error()
    {
        $this->setNewRelicEnvironmentSetUp();

        $this->bindNewRelicFakeClient('1.1.0', [
            'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12345)',
        ]);
        $this->fakeClient->push('failed_deploy');

        $this->artisan('app-version:create-deploy')
            ->assertFailed()
            ->expectsOutput('Error creating newrelic deployment');

        $this->fakeClient->assertLastRequestHas('query', <<<'GRAPHQL'
mutation ($deployment: DeploymentInput!) {
  changeTrackingCreateDeployment(deployment: $deployment) {
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
            ->assertFailed()
            ->expectsOutput(
                'You must execute app-version:create command before.'
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
            ->expectsOutput("SENTRY configuration is not valid:\n\t- The sentry.auth token field is required.\n\t- The sentry.organization field is required.")
            ->expectsOutput("NEWRELIC configuration is not valid:\n\t- The newrelic.api key field is required.\n\t- The newrelic.entity guid field is required.")
            ->doesntExpectOutput('You must execute app-version:create command before.');
    }
}
