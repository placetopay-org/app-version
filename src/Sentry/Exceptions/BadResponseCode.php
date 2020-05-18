<?php

namespace PlacetoPay\AppVersion\Sentry\Exceptions;

use Exception;
use PlacetoPay\AppVersion\Helpers\Response;

class BadResponseCode extends Exception
{
    public $response;

    /**
     * @var array
     */
    public $errors;

    public static function createForResponse(Response $response): self
    {
        $exception = new static(static::getMessageForResponse($response));

        $exception->response = $response;

        $bodyErrors = isset($response->getBody()['errors']) ? $response->getBody()['errors'] : [];

        $exception->errors = $bodyErrors;

        return $exception;
    }

    public static function getMessageForResponse(Response $response): string
    {
        return "Response code {$response->getHttpResponseCode()} returned";
    }
}
