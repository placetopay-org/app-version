<?php

namespace PlacetoPay\AppVersion\NewRelic;

use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Exceptions\UnsupportedException;
use PlacetoPay\AppVersion\Helpers\ChangelogLastChanges;
use PlacetoPay\AppVersion\Helpers\HttpClient;
use PlacetoPay\AppVersion\Helpers\Logger;
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
     */
    public function createDeploy(string $versionSha, string $environment, string $changelogFileName): array
    {
        $this->client->addHeaders([
            "API-Key: {$this->apiKey}",
        ]);

        try {
            $this->changelog->read($changelogFileName);
        } catch (ChangelogException $exception) {
            Logger::error('Error reading changelog file: ', ['exception' => $exception]);
        }

        return $this->client->post(self::API_URL, $this->buildGraphQLQuery($versionSha, $environment));
    }

    private function buildGraphQLQuery(string $versionSha, string $environment): array
    {
        $deployment = [
            'version' => $versionSha,
            'entityGuid' => $this->entityGuid,
            'changelog' => $this->parseChangelogData(),
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

    private function parseChangelogData(): string
    {
        $result = [];
        $currentKey = null;
        $content = $this->changelog->content();
        $availableKeys = [
            'feature',
            'refactor',
            'bugfix',
            'breaking changes',
            'continuous integration',
            'dependencies',
            'added',
            'changed',
            'deprecated',
            'removed',
            'fixed',
            'security',
        ];

        if (empty($content)) {
            return '';
        }

        foreach ($content as $change) {
            $normalizedKey = ltrim(strtolower($change), " \t\n\r\0\x0B\xE2\x9A\xA0");
            if (in_array($normalizedKey, $availableKeys)) {
                $currentKey = $normalizedKey;
                $result[$currentKey] = [];
            } elseif ($currentKey) {
                $result[$currentKey][] = $change;
            }
        }

        if (empty($result)) {
            $result = $content;
        }

        return json_encode([
            'version' => $this->changelog->version(),
            'content' => $result,
        ]);
    }

    public function createRelease(string $version, string $repository, string $sentryProject)
    {
        throw new UnsupportedException('Action createRelease not supported for NewRelic');
    }
}
