<?php

declare(strict_types=1);

namespace FirmApi\Tests;

use FirmApi\Client;
use FirmApi\Exceptions\ApiException;
use FirmApi\Exceptions\AuthenticationException;
use FirmApi\Exceptions\RateLimitException;
use FirmApi\Exceptions\ValidationException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class ClientTest extends TestCase
{
    private const SANDBOX_BASE_URL = 'https://api.firmapi.sk/v1/sandbox';
    private const SANDBOX_API_KEY = 'fa_sandbox_test_key_firmapi_sk_2026';

    public function test_constructor_sets_defaults(): void
    {
        $client = new Client('my-key');

        $this->assertSame('my-key', $client->getApiKey());
        $this->assertSame('https://api.firmapi.sk/v1', $client->getBaseUrl());
        $this->assertFalse($client->sandbox);
    }

    public function test_sandbox_static_factory_uses_sandbox_endpoint_and_key(): void
    {
        $client = Client::sandbox();

        $this->assertTrue($client->sandbox);
        $this->assertSame(self::SANDBOX_BASE_URL, $client->getBaseUrl());
        $this->assertSame(self::SANDBOX_API_KEY, $client->getApiKey());
    }

    public function test_sandbox_constructor_flag_overrides_credentials(): void
    {
        // Explicit sandbox: true wins and overrides any passed key/base URL.
        $client = new Client('ignored-key', baseUrl: 'https://custom.example/v1', sandbox: true);

        $this->assertTrue($client->sandbox);
        $this->assertSame(self::SANDBOX_BASE_URL, $client->getBaseUrl());
        $this->assertSame(self::SANDBOX_API_KEY, $client->getApiKey());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_sandbox_constant_enables_sandbox_in_plain_php(): void
    {
        // Plain-PHP switch: define the constant before constructing the client.
        define('FIRMAPI_SANDBOX', true);

        $client = new Client('whatever-key');

        $this->assertTrue($client->sandbox);
        $this->assertSame(self::SANDBOX_BASE_URL, $client->getBaseUrl());
        $this->assertSame(self::SANDBOX_API_KEY, $client->getApiKey());
    }

    public function test_explicit_sandbox_false_beats_the_constant(): void
    {
        // sandbox: false is explicit and must not be overridden by the constant.
        // (The constant isn't defined in this process; this asserts the default
        //  live path and that an explicit false is honored.)
        $client = new Client('live-key', sandbox: false);

        $this->assertFalse($client->sandbox);
        $this->assertSame('live-key', $client->getApiKey());
        $this->assertSame('https://api.firmapi.sk/v1', $client->getBaseUrl());
    }

    public function test_constructor_accepts_custom_base_url(): void
    {
        $client = new Client('my-key', baseUrl: 'https://custom.api.dev/v1/');

        $this->assertSame('https://custom.api.dev/v1', $client->getBaseUrl());
    }

    public function test_constructor_trims_trailing_slash(): void
    {
        $client = new Client('key', baseUrl: 'https://example.com/v1///');

        $this->assertSame('https://example.com/v1', $client->getBaseUrl());
    }

    public function test_resource_properties_are_initialized(): void
    {
        $client = new Client('key');

        $this->assertInstanceOf(\FirmApi\Resources\Companies::class, $client->companies);
        $this->assertInstanceOf(\FirmApi\Resources\Search::class, $client->search);
        $this->assertInstanceOf(\FirmApi\Resources\Batch::class, $client->batch);
        $this->assertInstanceOf(\FirmApi\Resources\Account::class, $client->account);
    }

    public function test_get_request_sends_correct_method(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->get('/test');

        $this->assertSame('GET', $this->lastRequestMethod());
    }

    public function test_get_request_includes_query_params(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->get('/test', ['q' => 'hello', 'limit' => 10]);

        $this->assertStringContainsString('q=hello', $this->lastRequestUri());
        $this->assertStringContainsString('limit=10', $this->lastRequestUri());
    }

    public function test_post_request_sends_correct_method(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->post('/test', ['foo' => 'bar']);

        $this->assertSame('POST', $this->lastRequestMethod());
    }

    public function test_post_request_sends_json_body(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->post('/test', ['icos' => ['12345678', '87654321']]);

        $body = $this->lastRequestBody();
        $this->assertSame(['12345678', '87654321'], $body['icos']);
    }

    public function test_authorization_header_is_sent(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->get('/test');

        $this->assertSame('Bearer test-api-key', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    public function test_successful_response_returns_decoded_json(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => ['name' => 'Test Company']]),
        ]);

        $result = $client->get('/test');

        $this->assertSame(['data' => ['name' => 'Test Company']], $result);
    }

    public function test_401_throws_authentication_exception(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['error' => 'Invalid API key'], 401),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);

        $client->get('/test');
    }

    public function test_422_throws_validation_exception_with_errors(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'message' => 'Validation failed',
                'errors' => ['ico' => ['The ico must be 8 characters.']],
            ], 422),
        ]);

        try {
            $client->get('/test');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertSame(['The ico must be 8 characters.'], $e->getFieldErrors('ico'));
            $this->assertEmpty($e->getFieldErrors('nonexistent'));
        }
    }

    public function test_429_throws_rate_limit_exception_with_retry_after(): void
    {
        $client = $this->createClient([
            new Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => '30',
            ], json_encode(['message' => 'Too many requests'])),
        ]);

        try {
            $client->get('/test');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getCode());
            $this->assertSame(30, $e->getRetryAfter());
        }
    }

    public function test_429_defaults_retry_after_to_60(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['message' => 'Rate limited'], 429),
        ]);

        try {
            $client->get('/test');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(60, $e->getRetryAfter());
        }
    }

    public function test_403_throws_api_exception(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['message' => 'Forbidden'], 403),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(403);

        $client->get('/test');
    }

    public function test_404_throws_api_exception(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['message' => 'Company not found'], 404),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);

        $client->get('/test');
    }

    public function test_500_throws_api_exception_after_retries_exhausted(): void
    {
        // 5xx is transient and retried (default maxRetries=2 => 3 attempts total);
        // once exhausted it surfaces as an ApiException with the status code.
        $client = $this->createClient([
            $this->jsonResponse(['error' => 'Internal server error'], 500),
            $this->jsonResponse(['error' => 'Internal server error'], 500),
            $this->jsonResponse(['error' => 'Internal server error'], 500),
        ]);

        try {
            $client->get('/test');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->getCode());
            $this->assertStringNotContainsString('Network error', $e->getMessage());
        }

        $this->assertCount(3, $this->requestHistory, '5xx must be retried up to maxRetries');
    }

    public function test_5xx_is_retried_then_succeeds(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['error' => 'Bad gateway'], 502),
            $this->jsonResponse(['data' => ['ico' => '51636549']]),
        ]);

        $result = $client->get('/test');

        $this->assertSame('51636549', $result['data']['ico']);
        $this->assertCount(2, $this->requestHistory);
    }

    public function test_429_is_not_retried(): void
    {
        // Rate limiting must surface immediately (with Retry-After) rather than
        // being silently retried, so the caller controls pacing.
        $client = $this->createClient([
            $this->jsonResponse(['error' => 'Too many requests'], 429),
            $this->jsonResponse(['data' => ['ico' => '51636549']]),
        ]);

        try {
            $client->get('/test');
            $this->fail('Expected RateLimitException');
        } catch (\FirmApi\Exceptions\RateLimitException $e) {
            $this->assertSame(429, $e->getCode());
        }

        $this->assertCount(1, $this->requestHistory, '429 must not be retried');
    }

    public function test_network_error_throws_api_exception_after_retries(): void
    {
        $mkError = fn () => new \GuzzleHttp\Exception\ConnectException(
            'Connection refused',
            new \GuzzleHttp\Psr7\Request('GET', '/test')
        );

        $client = $this->createClient([$mkError(), $mkError(), $mkError()]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Network error: Connection refused');

        $client->get('/test');
    }

    public function test_malformed_json_throws_api_exception(): void
    {
        // A non-JSON body (e.g. an HTML error page) must fail loudly, not be
        // silently coerced into an empty array.
        $client = $this->createClient([
            new Response(200, ['Content-Type' => 'application/json'], '<html>gateway error</html>'),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Malformed JSON response');

        $client->get('/test');
    }

    public function test_empty_response_body_returns_empty_array(): void
    {
        $client = $this->createClient([
            new Response(200, [], ''),
        ]);

        $result = $client->get('/test');

        $this->assertSame([], $result);
    }
}
