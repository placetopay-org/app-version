<?php

namespace PlacetoPay\AppVersion\Tests\Mocks;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert;
use PlacetoPay\AppVersion\Helpers\HttpClient;
use PlacetoPay\AppVersion\Helpers\Response;

class FakeClient extends HttpClient
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

    /**
     * @param $case
     */
    public function push($case)
    {
        if ($case === 'success_deploy') {
            $this->nextResponse = [
                'name' => null,
                'url' => null,
                'environment' => 'local',
                'dateStarted' => null,
                'dateFinished' => '2020-03-13T21:59:45.030847Z',
                'id' => '5563424',
            ];
        }

        if ($case === 'success_release') {
            $this->nextResponse = [
                'dateReleased' => null,
                'newGroups' => 0,
                'commitCount' => 0,
                'url' => null,
                'data' => [],
                'lastDeploy' => null,
                'deployCount' => 0,
                'dateCreated' => '2020-03-13T21:41:41.032208Z',
                'lastEvent' => null,
                'version' => 'aaaaab',
                'firstEvent' => null,
                'lastCommit' => null,
                'shortVersion' => 'aaaaab',
                'authors' => [],
                'owner' => null,
                'versionInfo' => [
                    'buildHash' => null,
                    'version' => [
                        'raw' => 'aaaaab',
                    ],
                    'description' => 'aaaaab',
                    'package' => null,
                ],
                'ref' => null,
                'projects' => [
                    0 => [
                        'id' => 4615782,
                        'name' => 'test-project',
                        'slug' => 'test-project',
                    ],
                ],
            ];
        }
    }
}
