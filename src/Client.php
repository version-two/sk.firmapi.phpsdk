<?php

declare(strict_types=1);

namespace FirmApi;

use FirmApi\Exceptions\ApiException;
use FirmApi\Exceptions\AuthenticationException;
use FirmApi\Exceptions\RateLimitException;
use FirmApi\Exceptions\ValidationException;
use FirmApi\Resources\Companies;
use FirmApi\Resources\Search;
use FirmApi\Resources\Batch;
use FirmApi\Resources\Account;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    private const DEFAULT_BASE_URL = 'https://api.firmapi.sk/v1';
    private const DEFAULT_TIMEOUT = 30;

    private HttpClient $http;
    private string $apiKey;
    private string $baseUrl;

    public readonly Companies $companies;
    public readonly Search $search;
    public readonly Batch $batch;
    public readonly Account $account;

    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        int $timeout = self::DEFAULT_TIMEOUT,
        ?HttpClient $httpClient = null
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');

        $this->http = $httpClient ?? new HttpClient([
            'base_uri' => $this->baseUrl . '/',
            'timeout' => $timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->companies = new Companies($this);
        $this->search = new Search($this);
        $this->batch = new Batch($this);
        $this->account = new Account($this);
    }

    /**
     * Make a GET request to the API.
     *
     * @param string $endpoint
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $endpoint
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make a request to the API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     * @throws ApiException
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->http->request($method, ltrim($endpoint, '/'), $options);
            $body = (string) $response->getBody();

            return json_decode($body, true) ?? [];
        } catch (BadResponseException $e) {
            $this->handleResponseException($e);
        } catch (GuzzleException $e) {
            throw new ApiException(
                'Network error: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Handle HTTP response exceptions (4xx and 5xx).
     *
     * @param BadResponseException $e
     * @throws ApiException
     */
    private function handleResponseException(BadResponseException $e): never
    {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true) ?? [];

        $message = $body['message'] ?? $body['error'] ?? $e->getMessage();

        match ($statusCode) {
            401 => throw new AuthenticationException($message, $statusCode),
            403 => throw new ApiException('Forbidden: ' . $message, $statusCode),
            404 => throw new ApiException('Not found: ' . $message, $statusCode),
            422 => throw new ValidationException($message, $body['errors'] ?? [], $statusCode),
            429 => throw new RateLimitException(
                $message,
                (int) ($response->getHeaderLine('Retry-After') ?: 60),
                $statusCode
            ),
            default => throw new ApiException($message, $statusCode),
        };
    }

    /**
     * Get the API key.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
