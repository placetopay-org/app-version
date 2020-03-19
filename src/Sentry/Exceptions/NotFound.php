<?php

namespace PlacetoPay\AppVersion\Sentry\Exceptions;

use PlacetoPay\AppVersion\Sentry\Http\Response;

class NotFound extends BadResponseCode
{
    /**
     * @param \PlacetoPay\AppVersion\Sentry\Http\Response $response
     * @return string
     */
    public static function getMessageForResponse(Response $response): string
    {
        return 'Not found';
    }
}
