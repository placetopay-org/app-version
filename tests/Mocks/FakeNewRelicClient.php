<?php

namespace PlacetoPay\AppVersion\Tests\Mocks;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert;
use PlacetoPay\AppVersion\Helpers\HttpClient;
use PlacetoPay\AppVersion\Helpers\Response;

class FakeNewRelicClient extends HttpClient
{
    /**
     * @var array
     */
    protected $requests = [];

    /**
     * @var array
     */
    private $nextResponse;

    public function makeCurlRequest(string $httpVerb, string $fullUrl, array $headers = [], array $arguments = []): Response
    {
        $this->requests[] = compact('httpVerb', 'fullUrl', 'headers', 'arguments');

        return new Response([], $this->nextResponse, '');
    }

    /**
     * @param $key
     * @param null $expectedContent
     */
    public function assertLastRequestHas($key, $expectedContent = null)
    {
        Assert::assertGreaterThan(0, count($this->requests), 'There were no requests sent');

        $lastPayload = Arr::last($this->requests)['arguments'];

        Assert::assertTrue(Arr::has($lastPayload, $key), 'The last payload doesnt have the expected key. ' . print_r($lastPayload, true));

        if ($expectedContent === null) {
            return;
        }

        $actualContent = Arr::get($lastPayload, $key);

        Assert::assertEquals($expectedContent, $actualContent);
    }

    public function lastRequest(): array
    {
        return array_pop($this->requests) ?: [];
    }

    /**
     * @param $case
     */
    public function push($case)
    {
        if ($case === 'success_deploy') {
            $this->nextResponse = [
                'data' => [
                    "changeTrackingCreateDeployment" => [
                        "deploymentId" => "8f34ef95-a457-4ff1-b4ef-c43105ec3d13",
                        "timestamp" => 1742230186539,
                    ],
                ],
            ];
        }
    }
}
