<?php

namespace PlacetoPay\AppVersion\Helpers;

use PlacetoPay\AppVersion\Exceptions\ChangelogException;

class ChangelogLastChanges
{
    public const REGEX_SECTIONS_FILE = '/^(?:##\s*)?\[?(?:v)?(Unreleased|\d+\.\d+(?:\.\d+)?)(?:\s*\(\d{4}-\d{2}-\d{2}\))?\]?(?:\([^)]+\))?/mi';
    public const UNRELEASED_SECTION = '/\bunreleased\b/i';

    private ?string $version = null;
    private ?array $content = [];

    /**
     * @throws ChangelogException
     */
    public function read(string $fileName): void
    {
        $this->validateFile($fileName);

        $handle = fopen($fileName, 'r');
        $content = [];

        while (($line = fgets($handle)) !== false) {
            if (preg_match(self::REGEX_SECTIONS_FILE, $line, $matches)) {
                $isUnreleasedSection = (bool)preg_match(self::UNRELEASED_SECTION, $this->version);
                $content = array_filter($content);
                if ($isUnreleasedSection && !empty($content)) {
                    return;
                }

                if ($this->version && !$isUnreleasedSection) {
                    break;
                }
                $this->version = $matches[1];
            } elseif ($this->version) {
                $content[] = trim($line);
            }
        }

        fclose($handle);
        $this->content = $this->cleanContent($content);
    }

    public function version(): ?string
    {
        return $this->version;
    }

    public function content(): ?array
    {
        return $this->content;
    }

    private function cleanContent($changes): array
    {
        $result = array_map(function ($line) {
            $cleanLine = ltrim($line, '+-*# ');
            return trim($cleanLine);
        }, $changes);

        return array_values(array_filter($result));
    }

    /**
     * @throws ChangelogException
     */
    public function validateFile(string $fileName): void
    {
        if (!file_exists($fileName)) {
            throw ChangelogException::forFileNotFound($fileName);
        }

        if (!is_readable($fileName)) {
            throw  ChangelogException::forNoPermissionsToReadTheFile($fileName);
        }
    }
}
