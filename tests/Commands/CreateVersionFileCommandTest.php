<?php

namespace PlacetoPay\AppVersion\Tests\Commands;

use PlacetoPay\AppVersion\Tests\TestCase;
use PlacetoPay\AppVersion\VersionFile;

class CreateVersionFileCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        VersionFile::delete();
    }

    /** @test */
    public function can_create_version_file()
    {
        $input = [
            'sha' => 'abcdef',
            'time' => '20200315170330',
            'branch' => 'master',
            'version' => '1.0.0',
        ];

        $this->artisan('app-version:create', [
            '--sha' => $input['sha'],
            '--time' => $input['time'],
            '--branch' => $input['branch'],
            '--tag' => $input['version'],
        ])->assertExitCode(0);

        $this->assertTrue(VersionFile::exists());
        $this->assertEquals(VersionFile::read(), $input);
    }

    /** @test */
    public function can_create_version_file_without_project_variable()
    {
        $input = [
            'sha' => 'abcdef',
            'time' => '20200315170330',
            'branch' => 'master',
            'version' => '1.0.0',
        ];

        $this->artisan('app-version:create', [
            '--sha' => $input['sha'],
            '--time' => $input['time'],
            '--branch' => $input['branch'],
            '--tag' => $input['version'],
        ])->assertExitCode(0);

        $this->assertTrue(VersionFile::exists());
        $this->assertEquals(VersionFile::read(), $input);
    }

    /** @test  */
    public function can_create_version_file_default_values()
    {
        $input = [
            'sha' => exec('git rev-parse HEAD'),
            'version' => exec('git describe --tags'),
            'branch' => exec('git symbolic-ref -q --short HEAD'),
            'time' => date('c'),
        ];

        $this->artisan('app-version:create')->assertSuccessful();
        $this->assertTrue(VersionFile::exists());
        $this->assertEquals(VersionFile::read(), $input);
    }

    protected function tearDown(): void
    {
        VersionFile::delete();
        parent::tearDown();
    }
}
