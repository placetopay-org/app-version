<?php

namespace PlacetoPay\AppVersion\Clickup\Parsers;

use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Helpers\ChangelogLastChanges;

class TasksFileParser
{
    public const REGEX_CLICKUP_IDENTIFIER = '/\(https:\/\/app\.clickup\.com\/t(?:\/(?<team>\d+))?\/(?<id>[\w-]+)\)/';
    private ChangelogLastChanges $changelog;

    public function __construct(ChangelogLastChanges $changelog)
    {
        $this->changelog = $changelog;
    }

    /**
     * @throws ChangelogException
     */
    public function tasksData(string $changelogFileName): ?array
    {
        $this->changelog->read($changelogFileName);

        $content = $this->changelog->content();
        if (!empty($content)) {
            $tasks = $this->extractTasks($content);

            if (!empty($tasks)) {
                return [
                    'version' => $this->changelog->version(),
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
