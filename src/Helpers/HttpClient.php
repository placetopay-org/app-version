<?php

namespace PlacetoPay\AppVersion\Helpers;

use CurlHandle;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;
use PlacetoPay\AppVersion\Sentry\Exceptions\InvalidData;
use PlacetoPay\AppVersion\Sentry\Exceptions\NotFound;

class HttpClient
{
    /**
     * @var int
     */
    private $timeout;

    /**
     * @var array
     */
    private $lastRequest;

    private $headers = [];

    /**
     * HttpClient constructor.
     */
    public function __construct(int $timeout = 10)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return array|false
     * @throws BadResponseCode
     */
    public function post(string $url, array $arguments = [])
    {
        return $this->makeRequest('post', $url, $arguments);
    }

    /**
     * @return array
     * @throws BadResponseCode
     */
    public function makeRequest(string $method, string $url, array $arguments = [])
    {
        $response = $this->makeCurlRequest($method, $url, $this->headers, $arguments);

        if ($response->getHttpResponseCode() === 422) {
            throw InvalidData::createForResponse($response);
        }

        if ($response->getHttpResponseCode() === 404) {
            throw NotFound::createForResponse($response);
        }

        if ($response->getHttpResponseCode() >= 300) {
            throw BadResponseCode::createForResponse($response);
        }

        return $response->getBody();
    }

    /**
     * @return Response
     */
    public function makeCurlRequest(string $httpVerb, string $fullUrl, array $headers, array $arguments)
    {
        $curlHandle = $this->getCurlHandle($fullUrl, $headers);

        switch ($httpVerb) {
            case 'post':
                curl_setopt($curlHandle, CURLOPT_POST, true);
                $this->attachRequestPayload($curlHandle, $arguments);
                break;
        }

        $body = json_decode(curl_exec($curlHandle), true);
        $headers = curl_getinfo($curlHandle);
        $error = curl_error($curlHandle);

        return new Response($headers, $body, $error);
    }

    private function getCurlHandle(string $fullUrl, array $headers = []): CurlHandle
    {
        $curlHandle = curl_init();

        curl_setopt($curlHandle, CURLOPT_URL, $fullUrl);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array_merge([
            'Accept: application/json',
            'Content-Type: application/json',
        ], $headers));

        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'PlacetoPay/AppVersion');
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curlHandle, CURLOPT_ENCODING, '');
        curl_setopt($curlHandle, CURLINFO_HEADER_OUT, true);

        return $curlHandle;
    }

    private function attachRequestPayload(CurlHandle $curlHandle, array $data)
    {
        $encoded = json_encode($data);
        $this->lastRequest['body'] = $encoded;
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $encoded);
    }

    public function addHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function headers(): array
    {
        return $this->headers;
    }
}
