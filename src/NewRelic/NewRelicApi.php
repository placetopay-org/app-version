<?php

namespace PlacetoPay\AppVersion\NewRelic;

use PlacetoPay\AppVersion\Exceptions\UnsupportedException;
use PlacetoPay\AppVersion\Helpers\HttpClient;

class NewRelicApi
{
    public const API_URL = 'https://api.newrelic.com/graphql';

    /**
     * @var HttpClient
     */
    private $client;

    private $apiKey;
    /**
     * @var string
     */
    private ?string $entityGuid;

    public function __construct(HttpClient $client, string $apiKey, ?string $entityGuid = null)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->entityGuid = $entityGuid;
    }

    /**
     * @param string $apiKey
     * @return \PlacetoPay\AppVersion\Sentry\SentryApi
     */
    public static function create(string $apiKey, ?string $entityGuid = null): self
    {
        return new self(new HttpClient(), $apiKey, $entityGuid);
    }

    public function createDeploy(string $version, string $environment)
    {
        $this->client->addHeaders([
            "API-Key: {$this->apiKey}",
        ]);

        return $this->client->post(self::API_URL, [
            'query' => $this->buildGraphQLQuery($version, $environment),
        ]);
    }

    private function buildGraphQLQuery(string $version, string $environment): string
    {
        return <<<GRAPHQL
        mutation {
          changeTrackingCreateDeployment(
            deployment: {
              version: "$version",
              entityGuid: "$this->entityGuid",
              changelog: "Not available right now"
              description: "Commit on $environment",
              user: "Not available right now",
            }
          ) {
            deploymentId
            timestamp
          }
        }
        GRAPHQL;
    }

    public function createRelease(string $version, string $repository, string $sentryProject)
    {
        throw new UnsupportedException('Action createRelease not supported for NewRelic');
    }

}
