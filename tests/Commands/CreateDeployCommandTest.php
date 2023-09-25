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

    public function test_create_newrelic_deploy_with_last_changelog(): void
    {
        $changelogContent = '# Changelog
            All notable changes to this project will be documented in this file.
            
            The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
            and this project adheres to [Semantic Versioning](https://semver.org/).
            
            ## [Unreleased]
            
            ## [0.0.2 (2023-09-25)](https://bitbucket.org/)
            
            ### Updated
            - Change number 1.
            - Change number 2. [TK-002](https://app.clickup.com/)
            
            ## [0.0.1 (2023-09-10)](https://bitbucket.org/)
            
            ### Fixed
            
            - Fix api parameters';

        $expectedChangelogContent = '# Changelog
            All notable changes to this project will be documented in this file.
            
            The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
            and this project adheres to [Semantic Versioning](https://semver.org/).
            
            ## [Unreleased]
            
            ## [0.0.2 (2023-09-25)](https://bitbucket.org/)
            
            ### Updated
            - Change number 1.
            - Change number 2. [TK-002](https://app.clickup.com/)
            
            ';

        file_put_contents(Changelog::path(), $changelogContent);

        $this->setNewRelicEnvironmentSetUp();
        $this->bindNewRelicFakeClient();
        $this->fakeClient->push('success_deploy');

        $this->artisan('app-version:create-deploy')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('deployment', [
            'revision' => 'asdfg2',
            'changelog' => $expectedChangelogContent,
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
