<?php

namespace PlacetoPay\AppVersion\Sentry\Exceptions;

use PlacetoPay\AppVersion\Helpers\Response;

class NotFound extends BadResponseCode
{
    public static function getMessageForResponse(Response $response): string
    {
        return 'Not found';
    }
}
