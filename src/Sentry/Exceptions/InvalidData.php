<?php

namespace PlacetoPay\AppVersion\Sentry\Exceptions;

use PlacetoPay\AppVersion\Sentry\Http\Response;

class InvalidData extends BadResponseCode
{
    /**
     * @param \PlacetoPay\AppVersion\Sentry\Http\Response $response
     * @return string
     */
    public static function getMessageForResponse(Response $response): string
    {
        return 'Invalid data found';
    }
}
