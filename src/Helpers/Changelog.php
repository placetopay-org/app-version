<?php

namespace PlacetoPay\AppVersion\Helpers;

use Illuminate\Support\Arr;
use RuntimeException;

class Changelog
{
    public const REGEX_SECTIONS_FILE = '/^[\+\s]*##\s*\[?(Unreleased|\d+\.\d+(?:\.\d+)?(?:\s*\(\d{4}-\d{2}-\d{2}\))?)\]?(?:\([^)]+\))?/m';
    public const REGEX_VERSION = '/^(?:##\s*)?\[?(Unreleased|\d+\.\d+(?:\.\d+)?)(?:\s*\(\d{4}-\d{2}-\d{2}\))?\]?/';

    public function lastChanges(array $version): array
    {
        $currentCommit = trim(shell_exec('git log -n 1 --pretty="%H"'));


        $deployCommit = Arr::get($version, 'sha');
        $deployBranch = Arr::get($version,'branch');

        if (!$deployCommit || !$deployBranch) {
            throw new RuntimeException('No se pudo obtener información de commit o rama.');
        }

        $changelogDiff = shell_exec("git diff $deployCommit $currentCommit -- changelog.md");

        $sections = preg_split(self::REGEX_SECTIONS_FILE, $changelogDiff, -1, PREG_SPLIT_DELIM_CAPTURE);

        $version = null;
        foreach ($sections as $section) {
            if (preg_match(self::REGEX_VERSION, $section, $matches)) {
                $version = array_pop($matches);
                continue;
            }

            if (!$version) {
                continue;
            }

            return ['version' => $version, 'information' => self::cleanChanges($section)];
        }

        return [];
    }

    private function cleanChanges(string $changes): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $changes);

        $result = array_filter(array_map(function($line) {
            $cleanLine = trim($line);

            if (strpos($cleanLine, '+- ') === 0) {
                $cleanLine = substr($cleanLine, 3);
            }
            return $cleanLine;
        }, $lines));

        return array_values($result);
    }
}
