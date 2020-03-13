<?php

namespace PlacetoPay\AppVersion\Sentry\Exceptions;

use Exception;
use PlacetoPay\AppVersion\Sentry\Http\Response;

class BadResponseCode extends Exception
{
    /**
     * @var \PlacetoPay\AppVersion\Sentry\Http\Response
     */
    public $response;

    /**
     * @var array
     */
    public $errors;

    /**
     * @param \PlacetoPay\AppVersion\Sentry\Http\Response $response
     * @return static
     */
    public static function createForResponse(Response $response): self
    {
        $exception = new static(static::getMessageForResponse($response));

        $exception->response = $response;

        $bodyErrors = isset($response->getBody()['errors']) ? $response->getBody()['errors'] : [];

        $exception->errors = $bodyErrors;

        return $exception;
    }

    /**
     * @param \PlacetoPay\AppVersion\Sentry\Http\Response $response
     * @return string
     */
    public static function getMessageForResponse(Response $response): string
    {
        return "Response code {$response->getHttpResponseCode()} returned";
    }
}
