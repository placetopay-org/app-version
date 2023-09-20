<?php

namespace PlacetoPay\AppVersion\Helpers;

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
            // The line breaks replacement is necessary to see it properly on the Newrelic panel
            $content = str_replace("\n", '\n\n', file_get_contents(self::path()));
        }
        return $content;
    }
}
