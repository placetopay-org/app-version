<?php

namespace PlacetoPay\AppVersion\Sentry;

use PlacetoPay\AppVersion\Helpers\HttpClient;

class SentryApi
{
    public const API_URL = 'https://sentry.io/api/0/';

    /**
     * @var HttpClient
     */
    private $client;

    private $apiKey;
    /**
     * @var string
     */
    private $organization;

    public function __construct(HttpClient $client, string $apiKey, string $organization)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->organization = $organization;
    }

    /**
     * @param string $apiKey
     * @param string $organization
     * @return \PlacetoPay\AppVersion\Sentry\SentryApi
     */
    public static function create(string $apiKey, string $organization): self
    {
        return new self(new HttpClient(), $apiKey, $organization);
    }

    public function createDeploy(string $version, string $environment)
    {
        $this->client->addHeaders([
            "Authorization: Bearer {$this->apiKey}",
        ]);

        return $this->client->post($this->constructUrl($version), [
            'environment' => $environment,
        ]);
    }

    public function createRelease(string $version, string $repository, string $sentryProject)
    {
        $this->client->addHeaders([
            "Authorization: Bearer {$this->apiKey}",
        ]);

        return $this->client->post($this->constructUrl(), [
            'version' =>  $version,
            'refs' =>  [
                ['repository' => $repository, 'commit' =>  $version],
            ],
            'projects' => [$sentryProject],
        ]);
    }

    public function constructUrl(string $version = null): string
    {
        $url = self::API_URL . 'organizations/' . $this->organization . '/releases/';
        if ($version) {
            $url .= $version . '/deploys/';
        }
        return $url;
    }
}
