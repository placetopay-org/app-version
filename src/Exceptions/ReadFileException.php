<?php

namespace PlacetoPay\AppVersion\Exceptions;

use Exception;

class ReadFileException extends Exception
{
    public static function forNoExistingFile(string $filePath): self
    {
        return new self("File $filePath does not exist.");
    }
}
