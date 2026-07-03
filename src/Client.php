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
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    private const DEFAULT_BASE_URL = 'https://api.firmapi.sk/v1';
    private const SANDBOX_BASE_URL = 'https://api.firmapi.sk/v1/sandbox';
    private const SANDBOX_API_KEY = 'fa_sandbox_test_key_firmapi_sk_2026';
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Plain-PHP sandbox switch: `define('FIRMAPI_SANDBOX', true);` before creating
     * a client forces sandbox mode without passing anything to the constructor.
     * (In Laravel use the FIRMAPI_SANDBOX env var; anywhere use Client::sandbox().)
     */
    public const SANDBOX_CONSTANT = 'FIRMAPI_SANDBOX';

    private HttpClient $http;
    private string $apiKey;
    private string $baseUrl;

    public readonly bool $sandbox;
    private bool $waitForFreshData;
    private int $maxStaleRetries;
    private int $maxRetries;

    public readonly Companies $companies;
    public readonly Search $search;
    public readonly Batch $batch;
    public readonly Account $account;

    /**
     * @param string          $apiKey          Your FirmAPI key (Bearer token).
     * @param string|null     $baseUrl         Override the API base URL (staging/self-hosted).
     * @param int             $timeout         Per-request HTTP timeout in seconds.
     * @param HttpClient|null $httpClient      Inject a preconfigured Guzzle client (tests).
     * @param bool            $waitForFreshData Opt-in: block and re-poll until the API reports
     *                                         non-stale data. Default FALSE -- the API already
     *                                         returns valid, precomputed data immediately and
     *                                         flags `meta.stale` only to signal that a background
     *                                         refresh is queued. Waiting trades multiple seconds
     *                                         of latency (and extra billed requests) for a
     *                                         marginal freshness gain, so it is off by default;
     *                                         opt in per call with CompanyQuery::fresh() instead.
     * @param int             $maxStaleRetries Max re-polls when waiting for fresh data.
     * @param int             $maxRetries      Automatic retries for transient failures (HTTP 5xx
     *                                         and network errors) with exponential backoff.
     *                                         HTTP 429 is never silently retried -- it surfaces
     *                                         as RateLimitException so the caller controls pacing.
     * @param bool|null       $sandbox         Force sandbox mode on/off. When null (default) it is
     *                                         auto-enabled if the FIRMAPI_SANDBOX constant is defined
     *                                         and truthy. In sandbox mode the base URL and API key are
     *                                         overridden with the public sandbox endpoint/key (no real
     *                                         key needed, demo data, no rate limits).
     */
    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        int $timeout = self::DEFAULT_TIMEOUT,
        ?HttpClient $httpClient = null,
        bool $waitForFreshData = false,
        int $maxStaleRetries = 3,
        int $maxRetries = 2,
        ?bool $sandbox = null,
    ) {
        // Plain-PHP switch: define('FIRMAPI_SANDBOX', true) enables sandbox with no
        // constructor args. An explicit $sandbox argument always wins over the constant.
        $this->sandbox = $sandbox ?? (defined(self::SANDBOX_CONSTANT) && constant(self::SANDBOX_CONSTANT));

        if ($this->sandbox) {
            $apiKey = self::SANDBOX_API_KEY;
            $baseUrl = self::SANDBOX_BASE_URL;
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
        $this->waitForFreshData = $waitForFreshData;
        $this->maxStaleRetries = max(0, $maxStaleRetries);
        $this->maxRetries = max(0, $maxRetries);

        $this->http = $httpClient ?? new HttpClient([
            'base_uri' => $this->baseUrl . '/',
            'timeout' => $timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->companies = new Companies($this, $this->waitForFreshData, $this->maxStaleRetries);
        $this->search = new Search($this);
        $this->batch = new Batch($this);
        $this->account = new Account($this);
    }

    /**
     * Create a sandbox client for testing.
     * No API key needed, uses demo data, no rate limits.
     */
    public static function sandbox(): static
    {
        return new static(
            apiKey: self::SANDBOX_API_KEY,
            sandbox: true,
        );
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
        // Only attach the `query` request option when there is something to
        // send. Passing `['query' => []]` makes Guzzle REPLACE the URI query
        // string with an empty one, which would strip any `?scope=...` that
        // CompanyQuery::get() builds into the endpoint path.
        $options = $query === [] ? [] : ['query' => $query];

        return $this->request('GET', $endpoint, $options);
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
     * Make a request to the API, retrying transient failures (5xx / network)
     * with exponential backoff. 4xx responses (including 429) are never retried;
     * they map straight to typed exceptions.
     *
     * @param string $method
     * @param string $endpoint
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     * @throws ApiException
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $this->http->request($method, ltrim($endpoint, '/'), $options);

                return $this->decode((string) $response->getBody());
            } catch (BadResponseException $e) {
                $status = $e->getResponse()->getStatusCode();

                // Retry only server-side transient failures, never 4xx.
                if ($status >= 500 && $attempt < $this->maxRetries) {
                    $this->backoff($attempt++);
                    continue;
                }

                $this->handleResponseException($e);
            } catch (ConnectException $e) {
                // Connection/DNS/timeout: transient, safe to retry.
                if ($attempt < $this->maxRetries) {
                    $this->backoff($attempt++);
                    continue;
                }

                throw new ApiException('Network error: ' . $e->getMessage(), 0, $e);
            } catch (GuzzleException $e) {
                throw new ApiException('Network error: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Decode a JSON response body, failing loudly on malformed payloads instead
     * of silently returning an empty array (which hides HTML error pages and
     * truncated responses from the caller).
     *
     * @return array<string, mixed>
     * @throws ApiException
     */
    private function decode(string $body): array
    {
        if (trim($body) === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // Genuinely malformed body (HTML error page, truncated response, ...).
            throw new ApiException('Malformed JSON response from API: ' . $e->getMessage(), 0, $e);
        }

        // A valid but non-object payload (e.g. literal null) is treated as an
        // empty result, preserving the SDK's long-standing contract.
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Sleep for an exponential backoff interval (0.25s, 0.5s, 1s, ... capped at 5s)
     * before the next transient-failure retry.
     */
    private function backoff(int $attempt): void
    {
        $micros = (int) min(5_000_000, 250_000 * (2 ** $attempt));
        usleep($micros);
    }

    /**
     * Handle HTTP response exceptions (4xx and non-retried 5xx).
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
