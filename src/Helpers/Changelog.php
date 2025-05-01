<?php

namespace PlacetoPay\AppVersion\Helpers;

use Illuminate\Support\Arr;
use PlacetoPay\AppVersion\Exceptions\ChangelogException;

class Changelog
{
    public const REGEX_SECTIONS_FILE = '/^[\+\s]*(?:##\s*)?\[?(Unreleased|\d+\.\d+(?:\.\d+)?(?:\s*\(\d{4}-\d{2}-\d{2}\))?)\]?(?:\([^)]+\))?/mi';
    public const REGEX_VERSION = '/^(?:##\s*)?\[?(Unreleased|\d+\.\d+(?:\.\d+)?)(?:\s*\(\d{4}-\d{2}-\d{2}\))?\]?/i';

    public const REGEX_NEW_CHANGES = '/^\+(?!\+).*/m';
    public const DEFAULT_VERSION = 'Unreleased';

    /**
     * @throws ChangelogException
     */
    public function lastChanges(array $version, string $fileName): array
    {
        $commitInformation = $this->commitInformation();
        $currentBranch = Arr::get($commitInformation, 'currentBranch');
        $currentCommit = Arr::get($commitInformation, 'currentCommit');

        $deployCommit = Arr::get($version, 'sha');
        $deployBranch = Arr::get($version, 'branch');

        if (!$deployCommit || !$deployBranch) {
            throw ChangelogException::forNoDeployConfiguration();
        }

        if ($currentBranch !== $deployBranch) {
            throw ChangelogException::forDifferentBranches();
        }

        $changelogDiff = $this->changelogDiff($deployCommit, $currentCommit, $fileName);

        if (empty($changelogDiff)) {
            Logger::warning("No changes were found in the file '$fileName'.", [
                'currentCommit' => $currentCommit,
                'currentBranch' => $currentBranch,
                'deployCommit' => $deployCommit,
                'deployBranch' => $deployBranch,
            ]);
            return [];
        }

        return $this->extractChanges($changelogDiff);
    }

    protected function commitInformation(): array
    {
        $currentCommit = trim(shell_exec('git log -n 1 --pretty="%H"'));
        $currentBranch = trim(shell_exec('git symbolic-ref --short HEAD'));

        return ['currentCommit' => $currentCommit, 'currentBranch' => $currentBranch];
    }

    private function cleanChanges($changes): array
    {
        preg_match_all(self::REGEX_NEW_CHANGES, $changes, $lines);
        $lines = reset($lines);

        $result = array_filter(array_map(function ($line) {
            $cleanLine = trim($line);
            if (strpos($cleanLine, '+') === 0) {
                $cleanLine = trim(substr($cleanLine, 1));
            }
            if (strpos($cleanLine, '-') === 0) {
                $cleanLine = trim(substr($cleanLine, 1));
            }
            return $cleanLine;
        }, $lines));

        return array_values($result);
    }

    public function changelogDiff(string $deployCommit, string $currentCommit, string $fileName): string
    {
        return shell_exec("git diff $deployCommit $currentCommit -- $fileName");
    }

    public function extractChanges(string $changelogDiff): array
    {
        $sections = preg_split(self::REGEX_SECTIONS_FILE, $changelogDiff, -1, PREG_SPLIT_DELIM_CAPTURE);
        $changes = '';

        $version = null;
        foreach ($sections as $section) {
            if (preg_match(self::REGEX_VERSION, $section, $matches)) {
                $version = array_pop($matches);
                continue;
            }

            if (!$version) {
                continue;
            }

            $changes = $section;
            break;
        }

        if (!$version) {
            $changes = $changelogDiff;
        }

        $changes = self::cleanChanges($changes);
        if (!empty($changes)) {
            return  ['version' => $version ?? self::DEFAULT_VERSION, 'information' => $changes];
        }

        return [];
    }
}
