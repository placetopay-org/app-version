<?php

namespace PlacetoPay\AppVersion\Helpers;

class Response
{
    /**
     * Response constructor.
     * @param mixed $headers
     * @param mixed $body
     * @param string $error
     */
    public function __construct(private $headers, private $body, private $error)
    {
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    public function hasBody(): bool
    {
        return $this->body != false;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return int|void
     */
    public function getHttpResponseCode()
    {
        if (!isset($this->headers['http_code'])) {
            return;
        }

        return (int)$this->headers['http_code'];
    }
}
