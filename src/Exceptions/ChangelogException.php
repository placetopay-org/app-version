<?php

namespace PlacetoPay\AppVersion\Exceptions;

use Exception;

class ChangelogException extends Exception
{
    public static function forFileNotFound(): self
    {
        return new self("Changelog file not found.");
    }

    public static function forNoPermissionsToReadTheFile(): self
    {
        return new self('The changelog file cannot be accessed.');
    }
}
