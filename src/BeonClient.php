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
            return $this->parse($response);
        } catch (GuzzleException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    /**
     * POST multipart form data (for OTP, media uploads).
     */
    public function postMultipart(string $endpoint, array $multipartData): array
    {
        try {
            $response = $this->http->post($endpoint, ['multipart' => $multipartData]);
            return $this->parse($response);
        } catch (GuzzleException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    /**
     * GET from the Beon API.
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->http->get($endpoint, ['query' => $query]);
            return $this->parse($response);
        } catch (GuzzleException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    protected function parse($response): array
    {
        $body = (string) $response->getBody();
        $json = json_decode($body, true);

        return array_merge(
            ['status_code' => $response->getStatusCode(), 'success' => true],
            is_array($json) ? $json : ['raw' => $body]
        );
    }

    protected function error(string $message, int $code = 500): array
    {
        return [
            'success'     => false,
            'status_code' => $code ?: 500,
            'error'       => $message,
        ];
    }
}
