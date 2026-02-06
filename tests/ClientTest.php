<?php

declare(strict_types=1);

namespace FirmApi\Tests;

use FirmApi\Client;
use FirmApi\Exceptions\ApiException;
use FirmApi\Exceptions\AuthenticationException;
use FirmApi\Exceptions\RateLimitException;
use FirmApi\Exceptions\ValidationException;
use GuzzleHttp\Psr7\Response;

class ClientTest extends TestCase
{
    public function test_constructor_sets_defaults(): void
    {
        $client = new Client('my-key');

        $this->assertSame('my-key', $client->getApiKey());
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

    public function test_500_throws_api_exception_with_status_code(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['error' => 'Internal server error'], 500),
        ]);

        try {
            $client->get('/test');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->getCode());
            $this->assertStringNotContainsString('Network error', $e->getMessage());
        }
    }

    public function test_network_error_throws_api_exception(): void
    {
        $client = $this->createClient([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request('GET', '/test')
            ),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Network error: Connection refused');

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
