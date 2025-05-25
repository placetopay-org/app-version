<?php

namespace PlacetoPay\AppVersion\NewRelic;

use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Exceptions\UnsupportedException;
use PlacetoPay\AppVersion\Helpers\ChangelogLastChanges;
use PlacetoPay\AppVersion\Helpers\HttpClient;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;

class NewRelicApi
{
    public const API_URL = 'https://api.newrelic.com/graphql';

    private HttpClient $client;

    private string $apiKey;
    private string $entityGuid;
    private ChangelogLastChanges $changelog;

    public function __construct(HttpClient $client, string $apiKey, string $entityGuid, ChangelogLastChanges $changelog = null)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->entityGuid = $entityGuid;
        $this->changelog = $changelog;
    }

    public static function create(string $apiKey, string $entityGuid): self
    {
        return new self(new HttpClient(), $apiKey, $entityGuid, new ChangelogLastChanges());
    }

    /**
     * @throws BadResponseCode
     * @throws ChangelogException
     */
    public function createDeploy(string $versionSha, string $environment)
    {
        $this->client->addHeaders([
            "API-Key: {$this->apiKey}",
        ]);
        $this->changelog->read('changelog.md');

        return $this->client->post(self::API_URL, $this->buildGraphQLQuery($versionSha, $environment));
    }

    private function buildGraphQLQuery(string $versionSha, string $environment): array
    {
        $deployment = [
            'version' => $versionSha,
            'entityGuid' => $this->entityGuid,
            'changelog' => json_encode([
                'version' => $this->changelog->version(),
                'content' => $this->changelog->content()
            ]),
            'description' => "Commit on $environment",
            'user' => 'Not available right now',
        ];

        $query = <<<'GRAPHQL'
mutation ($deployment: DeploymentInput!) {
  changeTrackingCreateDeployment(deployment: $deployment) {
    deploymentId
    timestamp
  }
}
GRAPHQL;

        return [
            'query' => $query,
            'variables' => [
                'deployment' => $deployment,
            ],
        ];
    }

    public function createRelease(string $version, string $repository, string $sentryProject)
    {
        throw new UnsupportedException('Action createRelease not supported for NewRelic');
    }
}
