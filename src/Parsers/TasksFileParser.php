<?php

namespace PlacetoPay\AppVersion\Parsers;

use PlacetoPay\AppVersion\Exceptions\ReadFileException;

class TasksFileParser
{
    public const REGEX_SECTIONS_FILE = '/^(?:##\s*)?\[?(Unreleased|\d+\.\d+(?:\.\d+)?(?:\s*\(\d{4}-\d{2}-\d{2}\))?)\]?(?:\([^)]+\))?/m';
    public const REGEX_VERSION = '/^(?:##\s*)?\[?(Unreleased|\d+\.\d+(?:\.\d+)?)(?:\s*\(\d{4}-\d{2}-\d{2}\))?\]?/';
    public const REGEX_CLICKUP_IDENTIFIER = '/\(https:\/\/app\.clickup\.com\/t(?:\/(?<team>\d+))?\/(?<id>[\w-]+)\)/';

    /**
     * @throws ReadFileException
     */
    public function getTasksData(?string $filePath = null): ?array
    {
        if (!file_exists($filePath)) {
            throw ReadFileException::forNoExistingFile($filePath);
        }

        $content = file_get_contents($filePath);
        if (empty($content)) {
            return null;
        }

        $sections = preg_split(self::REGEX_SECTIONS_FILE, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        $version = null;

        foreach ($sections as $section) {
            if (preg_match(self::REGEX_VERSION, $section, $matches)) {
                $version = array_pop($matches);
                continue;
            }

            if (!$version) {
                continue;
            }

            $tasks = $this->extractTasks($section);
            if (!empty($tasks)) {
                return [
                    'version' => $version,
                    'tasks' => $tasks,
                ];
            }
        }

        return null;
    }

    private function extractTasks(string $section): array
    {
        $tasks = [];
        $lines = explode("\n", $section);

        foreach ($lines as $line) {
            if (preg_match(self::REGEX_CLICKUP_IDENTIFIER, $line, $match)) {
                $tasks[] = [
                    'id' => $match['id'],
                    'team' => $match['team'] ?: null,
                ];
            }
        }

        return $tasks;
    }
}
