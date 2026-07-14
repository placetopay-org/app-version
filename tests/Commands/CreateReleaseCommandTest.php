<?php

namespace PlacetoPay\AppVersion\Tests\Commands;

use PlacetoPay\AppVersion\Tests\Mocks\InteractsWithFakeClient;
use PlacetoPay\AppVersion\Tests\TestCase;

class CreateReleaseCommandTest extends TestCase
{
    use InteractsWithFakeClient;

    public function test_it_can_create_a_release(): void
    {
        $this->setSentryEnvironmentSetUp();

        $this->bindSentryFakeClient();

        $this->fakeClient->push('success_release');

        $this->artisan('app-version:create-release')->assertExitCode(0);

        $this->fakeClient->assertLastRequestHas('version', 'asdfg2');
    }
}
