<?php

namespace PlacetoPay\AppVersion\Helpers;

class Changelog
{
    public static function path(): string
    {
        return app_path('CHANGELOG.md');
    }

    public static function exists(): bool
    {
        return file_exists(self::path());
    }

    public static function read(): string
    {
        $content = 'Not available right now';
        if (self::exists()) {
            $content = file_get_contents(self::path());
        }
        return $content;
    }
}
