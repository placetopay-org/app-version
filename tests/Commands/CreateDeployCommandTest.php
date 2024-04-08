<?php

namespace PlacetoPay\AppVersion\Tests\Commands;

use PlacetoPay\AppVersion\Tests\Mocks\InteractsWithFakeClient;
use PlacetoPay\AppVersion\Tests\TestCase;

class CreateDeployCommandTest extends TestCase
{
    use InteractsWithFakeClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->expectedChangelog = '## [0.0.2 (2023-09-25)](https://bitbucket.org/)

### Updated
- Change number 1.
- Change number 2. [TK-002](https://app.clickup.com/)

';
        $this->expectedDefaultMessage = 'Not Available';
    }

    /** @test */
    public function can_create_a_release_for_sentry(): void
    {
        $this->setSentryEnvironmentSetUp();

        $this->bindSentryFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('environment', 'testing');

        $this->assertStringContainsString('Authorization: Bearer', $this->fakeClient->lastRequest()['headers'][0]);
    }

    /** @test */
    public function can_create_a_deploy_for_newrelic_without_changelog(): void
    {
        $this->setNewRelicEnvironmentSetUp();

        $this->bindNewRelicFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('deployment', [
            'revision' => 'asdfg2',
            'changelog' => $this->expectedDefaultMessage,
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]);

        $this->assertEquals(
            $this->fakeClient->lastRequest()['headers'][0],
            'X-Api-Key: ' . config('app-version.newrelic.api_key')
        );
    }

    /** @test */
    public function can_create_a_newrelic_deploy_with_wrong_changelog_name(): void
    {
        $changelogName = base_path('changelog.md');
        copy('tests/Mocks/CHANGELOG.md', $changelogName);

        $this->setNewRelicEnvironmentSetUp();
        $this->bindNewRelicFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('deployment', [
            'revision' => 'asdfg2',
            'changelog' => $this->expectedChangelog,
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]);

        $this->assertEquals(
            $this->fakeClient->lastRequest()['headers'][0],
            'X-Api-Key: ' . config('app-version.newrelic.api_key')
        );

        unlink($changelogName);
    }

    /** @test */
    public function can_create_a_newrelic_deploy_with_wrong_format(): void
    {
        $changelogName = base_path('CHANGELOG.md');
        copy('tests/Mocks/WRONG-CHANGELOG.md', $changelogName);

        $this->setNewRelicEnvironmentSetUp();
        $this->bindNewRelicFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('deployment', [
            'revision' => 'asdfg2',
            'changelog' => $this->expectedDefaultMessage,
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]);

        $this->assertEquals(
            $this->fakeClient->lastRequest()['headers'][0],
            'X-Api-Key: ' . config('app-version.newrelic.api_key')
        );

        unlink($changelogName);
    }

    /** @test */
    public function can_create_a_newrelic_deploy_with_last_changelog(): void
    {
        $changelogName = base_path('CHANGELOG.md');
        copy('tests/Mocks/CHANGELOG.md', $changelogName);

        $this->setNewRelicEnvironmentSetUp();
        $this->bindNewRelicFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('deployment', [
            'revision' => 'asdfg2',
            'changelog' => $this->expectedChangelog,
            'description' => 'Commit on testing',
            'user' => 'Not available right now',
        ]);

        $this->assertEquals(
            $this->fakeClient->lastRequest()['headers'][0],
            'X-Api-Key: ' . config('app-version.newrelic.api_key')
        );

        unlink($changelogName);
    }
}
