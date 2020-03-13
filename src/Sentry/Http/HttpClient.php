<?php

namespace PlacetoPay\AppVersion\Sentry\Http;

use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;
use PlacetoPay\AppVersion\Sentry\Exceptions\InvalidData;
use PlacetoPay\AppVersion\Sentry\Exceptions\NotFound;

class HttpClient
{
    /**
     * @var string
     */
    private $apiToken;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var array
     */
    private $lastRequest;

    /**
     * HttpClient constructor.
     * @param string $apiToken
     * @param string $baseUrl
     * @param int $timeout
     */
    public function __construct(string $apiToken, string $baseUrl = 'https://sentry.io/api/0', int $timeout = 10)
    {
        $this->apiToken = $apiToken;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
    }

    /**
     * @param string $url
     * @param array $arguments
     *
     * @return array|false
     * @throws \PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode
     */
    public function post(string $url, array $arguments = [])
    {
        return $this->makeRequest('post', $url, $arguments);
    }

    /**
     * @param string $httpVerb
     * @param string $url
     * @param array $arguments
     *
     * @return array
     * @throws \PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode
     */
    public function makeRequest(string $httpVerb, string $url, array $arguments = [])
    {
        $headers = ["Authorization: Bearer {$this->apiToken}"];

        $fullUrl = "{$this->baseUrl}/{$url}";

        $response = $this->makeCurlRequest($httpVerb, $fullUrl, $headers, $arguments);

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
     * @param string $httpVerb
     * @param string $fullUrl
     * @param array $headers
     * @param array $arguments
     * @return \PlacetoPay\AppVersion\Sentry\Http\Response
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

    /**
     * @param string $fullUrl
     * @param array $headers
     *
     * @return resource
     */
    private function getCurlHandle(string $fullUrl, array $headers = [])
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

    /**
     * @param $curlHandle
     * @param array $data
     */
    private function attachRequestPayload(&$curlHandle, array $data)
    {
        $encoded = json_encode($data);

        $this->lastRequest['body'] = $encoded;
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $encoded);
    }
}
