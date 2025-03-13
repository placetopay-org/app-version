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
    private $applicationId;
    private $entityGuid;

    public function __construct(HttpClient $client, string $apiKey, string $applicationId, ?string $entityGuid = null)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->applicationId = $applicationId;
        $this->entityGuid = $entityGuid;
    }

    /**
     * @param string $apiKey
     * @param string $applicationId
     * @return \PlacetoPay\AppVersion\Sentry\SentryApi
     */
    public static function create(string $apiKey, string $applicationId, ?string $entityGuid = null): self
    {
        return new self(new HttpClient(), $apiKey, $applicationId, $entityGuid);
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
        $entityGuid = $this->getEntityGuid();
        return <<<GRAPHQL
        mutation {
          changeTrackingCreateDeployment(
            deployment: {
              version: "$version",
              entityGuid: "$entityGuid",
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

    public function getEntityGuid()
    {
        if (!empty($this->entityGuid)) {
            return $this->entityGuid;
        }

        $response = $this->client->post(self::API_URL, [
            'query' => $this->buildEntitySearchQuery(),
        ]);

        return $this->extractEntityGuid($response);
    }

    private function buildEntitySearchQuery(): string
    {
        return <<<GRAPHQL
        {
            actor {
                entitySearch(query: "domainId=$this->applicationId") {
                    count
                    query
                    results {
                        entities {
                            entityType
                            name
                            guid
                        }
                    }
                }
            }
        }
        GRAPHQL;
    }

    private function extractEntityGuid($response)
    {
        if ($response
            && isset($response['data']['actor']['entitySearch']['count'])
            && $response['data']['actor']['entitySearch']['count'] > 0
        ) {
            return $response['data']['actor']['entitySearch']['results']['entities'][0]['guid'];
        }

        return null;
    }

    public function createRelease(string $version, string $repository, string $sentryProject)
    {
        throw new UnsupportedException('Action createRelease not supported for NewRelic');
    }

}
