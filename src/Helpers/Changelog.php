<?php

namespace PlacetoPay\AppVersion\Helpers;

class Changelog
{
    protected const PATH = 'CHANGELOG.md';

    public static function exists(): bool
    {
        return file_exists(self::PATH);
    }

    public static function read(): string
    {
        $content = 'Not available right now';
        if (self::exists()) {
            $content = file_get_contents(self::PATH);
        }
        return $content;
    }
}
