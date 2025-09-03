<?php

namespace PlacetoPay\AppVersion\Exceptions;

use Exception;

class BadResponseException extends Exception
{
    public static function forUnsuccessfulResponse(string $reason): self
    {
        return new self('Unsuccessful response: ' . $reason);
    }
}
