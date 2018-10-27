<?php


class VersionTest extends Orchestra\Testbench\TestCase
{

    protected function getPackageProviders($app)
    {
        return [\PlacetoPay\AppVersion\VersionServiceProvider::class];
    }

    public function testItVisitsTheInformationEndpoint()
    {
        $response = $this->get('/version');

        $this->assertEquals(200, $response->status());

        $data = $response->json();

        $this->assertArrayHasKey('hash', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('branch', $data);
        $this->assertArrayHasKey('date', $data);
    }

}
