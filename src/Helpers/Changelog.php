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

    public static function read(int $charactersLimit = 0): string
    {
        $content = 'Not available right now';
        if (self::exists()) {
            $content = file_get_contents(self::path());
            if ($charactersLimit > 0 && strlen($content) > $charactersLimit) {
                $content = substr($content, 0, $charactersLimit);
            }
        }
        return $content;
    }
}
