<?php

namespace PlacetoPay\AppVersion\Clickup\Parsers;

use Illuminate\Support\Arr;
use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Helpers\Changelog;

class TasksFileParser
{
    public const REGEX_CLICKUP_IDENTIFIER = '/\(https:\/\/app\.clickup\.com\/t(?:\/(?<team>\d+))?\/(?<id>[\w-]+)\)/';
    private Changelog $changelog;

    public function __construct(Changelog $changelog)
    {
        $this->changelog = $changelog;
    }

    /**
     * @throws ChangelogException
     */
    public function tasksData(array $version, string $changelogFileName): ?array
    {
        $changelogInformation = $this->changelog->lastChanges($version, $changelogFileName);

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
