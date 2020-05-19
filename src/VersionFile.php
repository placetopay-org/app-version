<?php

namespace PlacetoPay\AppVersion;

use Illuminate\Support\Facades\Storage;

class VersionFile
{
    public static function path(): string
    {
        return 'app-version.json';
    }

    public static function generate($data)
    {
        return Storage::put(self::path(), json_encode($data));
    }

    public static function exists()
    {
        return Storage::exists(self::path());
    }

    public static function delete(): void
    {
        if (self::exists()) {
            Storage::delete(self::path());
        }
    }

    public static function read(): array
    {
        if (self::exists()) {
            return json_decode(Storage::get(self::path()), JSON_OBJECT_AS_ARRAY);
        }

        return [];
    }

    /**
     * You should only read the sha by using a config variable, given it should be in cache.
     * @return string
     */
    public static function readSha(): string
    {
        return self::read()['sha'] ?? '';
    }
}
