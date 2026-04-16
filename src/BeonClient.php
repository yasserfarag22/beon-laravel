<?php

namespace Beon\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

class BeonClient
{
    protected Client $http;
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct(string $baseUrl, string $apiKey, int $timeout = 30, ?HandlerStack $handlerStack = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey  = $apiKey;

        $config = [
            'base_uri' => $this->baseUrl,
            'timeout'  => $timeout,
            'headers'  => [
                'beon-token' => $apiKey,
                'Accept'     => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        if ($handlerStack) {
            $config['handler'] = $handlerStack;
        }

        $this->http = new Client($config);
    }

    /**
     * POST to the Beon API.
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->http->post($endpoint, ['json' => $data]);
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleException($e);
        }
    }

    /**
     * POST multipart form data (for OTP, media uploads).
     */
    public function postMultipart(string $endpoint, array $multipartData): array
    {
        try {
            $response = $this->http->post($endpoint, ['multipart' => $multipartData]);
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleException($e);
        }
    }

    /**
     * GET from the Beon API.
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->http->get($endpoint, ['query' => $query]);
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleException($e);
        }
    }

    /**
     * Handle the API response.
     *
     * @throws Exceptions\ApiException
     */
    protected function handleResponse($response): array
    {
        $body = (string) $response->getBody();
        $json = json_decode($body, true);
        $data = is_array($json) ? $json : ['raw' => $body];

        if ($response->getStatusCode() >= 400) {
            throw new Exceptions\ApiException(
                $data['error'] ?? $data['message'] ?? 'API Error',
                $response->getStatusCode(),
                $data
            );
        }

        return array_merge(['status_code' => $response->getStatusCode(), 'success' => true], $data);
    }

    /**
     * Handle Guzzle exceptions.
     */
    protected function handleException(GuzzleException $e): Exceptions\BeonException
    {
        if ($e instanceof \GuzzleHttp\Exception\ClientException || $e instanceof \GuzzleHttp\Exception\ServerException) {
            $response = $e->getResponse();
            $body = (string) $response->getBody();
            $json = json_decode($body, true);
            $data = is_array($json) ? $json : ['raw' => $body];

            return new Exceptions\ApiException(
                $data['error'] ?? $data['message'] ?? $e->getMessage(),
                $response->getStatusCode(),
                $data,
                $e
            );
        }

        return new Exceptions\BeonException($e->getMessage(), $e->getCode(), $e);
    }
}

