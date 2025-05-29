<?php

namespace PlacetoPay\AppVersion\Exceptions;

use Exception;

class ChangelogException extends Exception
{
    public static function forFileNotFound(string $fileName): self
    {
        return new self("$fileName file not found.");
    }

    public static function forNoPermissionsToReadTheFile(string $fileName): self
    {
        return new self("The $fileName file cannot be accessed.");
    }
}
