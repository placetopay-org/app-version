<?php

namespace PlacetoPay\AppVersion\Sentry;

use PlacetoPay\AppVersion\Sentry\Http\HttpClient;

class SentryApi
{
    /**
     * @var \PlacetoPay\AppVersion\Sentry\Http\HttpClient
     */
    private $client;

    /**
     * @var string
     */
    private $organization;

    /**
     * SentryApi constructor.
     * @param \PlacetoPay\AppVersion\Sentry\Http\HttpClient $client
     * @param string $organization
     */
    public function __construct(HttpClient $client, string $organization)
    {
        $this->client = $client;
        $this->organization = $organization;
    }

    /**
     * @param string $apiKey
     * @param string $organization
     * @return \PlacetoPay\AppVersion\Sentry\SentryApi
     */
    public static function create(string $apiKey, string $organization): self
    {
        return new self(new HttpClient($apiKey), $organization);
    }

    /**
     * @param string $version
     * @param string $environment
     * @return array|false
     * @throws \PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode
     */
    public function createDeploy(string $version, string $environment)
    {
        return $this->client->post("organizations/{$this->organization}/releases/{$version}/deploys/", [
            'environment' => $environment,
        ]);
    }

    /**
     * @param string $version
     * @param string $repository
     * @param string $sentryProject
     * @return array|false
     * @throws \PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode
     */
    public function createRelease(string $version, string $repository, string $sentryProject)
    {
        return $this->client->post("organizations/{$this->organization}/releases/", [
            'version' =>  $version,
            'refs' =>  [
                ['repository' => $repository, 'commit' =>  $version],
            ],
            'projects' => [$sentryProject],
        ]);
    }
}
