<?php

declare(strict_types=1);

namespace FirmApi\Tests;

use FirmApi\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected MockHandler $mockHandler;

    /** @var array<array{request: \GuzzleHttp\Psr7\Request, response: Response}> */
    protected array $requestHistory = [];

    protected function createClient(array $responses = []): Client
    {
        $this->mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mockHandler);

        $this->requestHistory = [];
        $handlerStack->push(Middleware::history($this->requestHistory));

        $httpClient = new HttpClient([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.firmapi.sk/v1/',
            'headers' => [
                'Authorization' => 'Bearer test-api-key',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        return new Client(
            apiKey: 'test-api-key',
            httpClient: $httpClient,
        );
    }

    protected function jsonResponse(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    protected function lastRequest(): \GuzzleHttp\Psr7\Request
    {
        return end($this->requestHistory)['request'];
    }

    protected function lastRequestUri(): string
    {
        return (string) $this->lastRequest()->getUri();
    }

    protected function lastRequestMethod(): string
    {
        return $this->lastRequest()->getMethod();
    }

    protected function lastRequestBody(): array
    {
        return json_decode((string) $this->lastRequest()->getBody(), true) ?? [];
    }
}
