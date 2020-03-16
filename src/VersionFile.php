<?php

namespace PlacetoPay\AppVersion;

class VersionFile
{
    public static function path(): string
    {
        return storage_path('app/app-version.json');
    }

    public static function generate($data)
    {
        return file_put_contents(self::path(), json_encode($data));
    }

    public static function exists()
    {
        return file_exists(self::path());
    }

    public static function delete(): void
    {
        if (self::exists()) {
            unlink(self::path());
        }
    }

    public static function read(): array
    {
        if (self::exists()) {
            return json_decode(file_get_contents(self::path()), JSON_OBJECT_AS_ARRAY);
        }

        return [];
    }

    /**
     * You should only read the sha by using a config variable, given it should be in cache.
     * @return string
     */
    public static function readSha(): string
    {
        return self::read()['sha'];
    }
}
