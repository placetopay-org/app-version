<?php

namespace PlacetoPay\AppVersion\NewRelic;

use PlacetoPay\AppVersion\Exceptions\UnsupportedException;
use PlacetoPay\AppVersion\Helpers\HttpClient;

class NewRelicApi
{
    public const API_URL = 'https://api.newrelic.com/v2/applications/';

    /**
     * @var HttpClient
     */
    private $client;

    private $apiKey;
    /**
     * @var string
     */
    private $applicationId;

    public function __construct(HttpClient $client, string $apiKey, string $applicationId)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->applicationId = $applicationId;
    }

    /**
     * @param string $apiKey
     * @param string $applicationId
     * @return \PlacetoPay\AppVersion\Sentry\SentryApi
     */
    public static function create(string $apiKey, string $applicationId): self
    {
        return new self(new HttpClient(), $apiKey, $applicationId);
    }

    public function createDeploy(string $version, string $environment)
    {
        $this->client->addHeaders([
            "X-Api-Key: {$this->apiKey}",
        ]);

        return $this->client->post($this->constructUrl(), [
            'deployment' => [
                'revision' => $version,
                'changelog' => 'Not available right now',
                'description' => 'Commit on ' . $environment,
                'user' => 'Not available right now',
            ],
        ]);
    }

    public function createRelease(string $version, string $repository, string $sentryProject)
    {
        throw new UnsupportedException('Action createRelease not supported for NewRelic');
    }

    public function constructUrl(): string
    {
        return self::API_URL . $this->applicationId . '/deployments.json';
    }
}
