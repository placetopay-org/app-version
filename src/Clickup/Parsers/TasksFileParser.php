<?php

namespace PlacetoPay\AppVersion\Parsers;

use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use PlacetoPay\AppVersion\Exceptions\ReadFileException;
use PlacetoPay\AppVersion\Helpers\Changelog;
use PlacetoPay\AppVersion\Helpers\HttpClient;

class TasksFileParser
{
    public const REGEX_CLICKUP_IDENTIFIER = '/\(https:\/\/app\.clickup\.com\/t(?:\/(?<team>\d+))?\/(?<id>[\w-]+)\)/';
    private Changelog $changelog;

    public function __construct(Changelog $changelog)
    {
        $this->changelog = $changelog;
    }

    public function tasksData(array $version): ?array
    {
        $changelogInformation = $this->changelog->lastChanges($version);

        if (!empty($changelogInformation)) {
            $tasks = $this->extractTasks(Arr::get($changelogInformation, 'information'));

            if (!empty($tasks)) {
                return [
                    'version' => Arr::get($changelogInformation, 'version'),
                    'tasks' => $tasks,
                ];
            }
        }

        return null;
    }

    private function extractTasks(array $changes): array
    {
        $tasks = [];

        foreach ($changes as $change) {
            if (preg_match(self::REGEX_CLICKUP_IDENTIFIER, $change, $match)) {
                $tasks[] = [
                    'id' => $match['id'],
                    'team' => $match['team'] ?: null,
                ];
            }
        }

        return $tasks;
    }
}
