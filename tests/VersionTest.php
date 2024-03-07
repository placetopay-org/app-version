<?php

namespace PlacetoPay\AppVersion\Tests;

use PlacetoPay\AppVersion\VersionFile;

class VersionTest extends TestCase
{
    /** @test */
    public function testItVisitsTheInformationEndpoint()
    {
        $response = $this
            ->get('/version?token=' . config('app-version.token'));

        $this->assertEquals(200, $response->status());

        $data = $response->json();

        $this->assertArrayHasKey('hash', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('branch', $data);
        $this->assertArrayHasKey('date', $data);
    }

    /** @test */
    public function it_returns_version_file_content()
    {
        $input = [
            'sha' => 'abcdef',
            'time' => '20200315170330',
            'project' => 'test-project',
            'branch' => 'master',
        ];

        VersionFile::generate($input);

        $this->get('/version?token=' . config('app-version.token'))
            ->assertSuccessful()
            ->assertJson($input);
    }

    /** @test */

    /** @test */
    public function testItVisitsTheInformationEndpointWithoutQueryParam()
    {
        $response = $this->get('/version');

        $this->assertEquals(404, $response->status());
    }

    /** @test */
    public function testItVisitsTheInformationEndpointWithQueryParamWrong()
    {
        $response = $this->get('/version?token=wrong');

        $this->assertEquals(404, $response->status());
    }
}
