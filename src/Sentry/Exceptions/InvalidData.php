<?php

namespace PlacetoPay\AppVersion\Sentry\Exceptions;

use PlacetoPay\AppVersion\Helpers\Response;

class InvalidData extends BadResponseCode
{
    public static function getMessageForResponse(Response $response): string
    {
        return 'Invalid data found';
    }
}
