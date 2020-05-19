<?php

namespace PlacetoPay\AppVersion\Helpers;

class Response
{
    /**
     * @var mixed
     */
    private $headers;

    /**
     * @var mixed
     */
    private $body;

    /**
     * @var string
     */
    private $error;

    /**
     * Response constructor.
     * @param $headers
     * @param $body
     * @param $error
     */
    public function __construct($headers, $body, $error)
    {
        $this->headers = $headers;
        $this->body = $body;
        $this->error = $error;
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

    /**
     * @return bool
     */
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
