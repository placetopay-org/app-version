<?php

namespace PlacetoPay\AppVersion\Exceptions;

use Exception;

class ConnectionException extends Exception
{
    public static function forNoConnectionService(string $reason): self
    {
        return new self('Could not establish connection to the server: ' . $reason);
    }
}
