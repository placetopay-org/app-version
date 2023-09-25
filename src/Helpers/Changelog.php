<?php

namespace PlacetoPay\AppVersion\Helpers;

/**
 * The Changelog format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
 */
class Changelog
{
    public static function path(): string
    {
        return base_path('CHANGELOG.md');
    }

    public static function exists(): bool
    {
        return file_exists(self::path());
    }

    public static function read(): string
    {
        $content = 'Not available right now';
        if (self::exists()) {
            $content = self::getLastVersion(file_get_contents(self::path()));
        }

        return $content;
    }

    /**
     * Returns the content up to the beginning of the second H2 of the Markdown
     * that meets the following regular expression: /##\s\[\d/ .
     */
    private static function getLastVersion(string $content): string
    {
        preg_match_all("/##\s\[\d/", $content, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches) === 1 && count($matches[0]) > 1) {
            $content = substr($content, 0, $matches[0][1][1]);
        }

        return $content;
    }
}
