<?php

namespace PlacetoPay\AppVersion\NewRelic;

use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Exceptions\UnsupportedException;
use PlacetoPay\AppVersion\Helpers\Changelog;
use PlacetoPay\AppVersion\Helpers\HttpClient;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;
use PlacetoPay\AppVersion\VersionFile;

class NewRelicApi
{
    public const API_URL = 'https://api.newrelic.com/graphql';

    private HttpClient $client;

    private string $apiKey;
    private string $entityGuid;
    private Changelog $changelog;

    public function __construct(HttpClient $client, string $apiKey, string $entityGuid, Changelog $changelog = null)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->entityGuid = $entityGuid;
        $this->changelog = $changelog;
    }

    public static function create(string $apiKey, string $entityGuid): self
    {
        return new self(new HttpClient(), $apiKey, $entityGuid, new Changelog());
    }

    /**
     * @throws BadResponseCode
     * @throws ChangelogException
     */
    public function createDeploy(string $version, string $environment)
    {
        $this->client->addHeaders([
            "API-Key: {$this->apiKey}",
        ]);
        $changelogData = $this->changelog->lastChanges(VersionFile::read(), 'changelog.md');

        return $this->client->post(self::API_URL, $this->buildGraphQLQuery($version, $environment, $changelogData));
    }

    private function buildGraphQLQuery(string $version, string $environment, array $changelog): array
    {
        $deployment = [
            'version' => $version,
            'entityGuid' => $this->entityGuid,
            'changelog' => json_encode($changelog),
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
