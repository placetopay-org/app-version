<?php

namespace PlacetoPay\AppVersion\Helpers;

/**
 * The Changelog format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
 */
class Changelog
{
    public const H2_REGEX = "/^##\s\[v?(\d+\.){2}\d+.*]/m";

    public const DEFAULT_MESSAGE = 'Not Available';

    public const FILENAME_REGEX = "/(?i)changelog(?-i)\.md/";

    public static function path(): string
    {
        $basePath = base_path();
        $files = scandir($basePath);

        $matches = preg_grep(self::FILENAME_REGEX, $files);
        if ($matches) {
            return  $basePath . DIRECTORY_SEPARATOR . array_pop($matches);
        }

        return '';
    }

    public static function read(): string
    {
        $path = self::path();
        if ($path) {
            return self::getLastVersion(file_get_contents($path));
        }

        return self::DEFAULT_MESSAGE;
    }

    /**
     * Returns the content from the first H2 to the beginning of the second H2 that meets the H2_REGEX.
     */
    private static function getLastVersion(string $content): string
    {
        preg_match_all(self::H2_REGEX, $content, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches) === 2 && count($matches[0]) > 1) {
            $firstH2Position = $matches[0][0][1];
            $secondH2Position = $matches[0][1][1];
            return substr($content, $firstH2Position, $secondH2Position - $firstH2Position);
        }

        return self::DEFAULT_MESSAGE;
    }
}
