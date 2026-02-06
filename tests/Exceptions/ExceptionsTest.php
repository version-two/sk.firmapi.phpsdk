<?php

declare(strict_types=1);

namespace FirmApi\Tests\Exceptions;

use FirmApi\Exceptions\ApiException;
use FirmApi\Exceptions\AuthenticationException;
use FirmApi\Exceptions\RateLimitException;
use FirmApi\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_api_exception_defaults(): void
    {
        $e = new ApiException();

        $this->assertSame('An API error occurred', $e->getMessage());
        $this->assertSame(0, $e->getCode());
    }

    public function test_api_exception_with_custom_values(): void
    {
        $previous = new \RuntimeException('cause');
        $e = new ApiException('Custom error', 500, $previous);

        $this->assertSame('Custom error', $e->getMessage());
        $this->assertSame(500, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function test_authentication_exception_defaults(): void
    {
        $e = new AuthenticationException();

        $this->assertSame('Authentication failed', $e->getMessage());
        $this->assertSame(401, $e->getCode());
        $this->assertInstanceOf(ApiException::class, $e);
    }

    public function test_rate_limit_exception_defaults(): void
    {
        $e = new RateLimitException();

        $this->assertSame('Rate limit exceeded', $e->getMessage());
        $this->assertSame(429, $e->getCode());
        $this->assertSame(60, $e->getRetryAfter());
        $this->assertInstanceOf(ApiException::class, $e);
    }

    public function test_rate_limit_exception_custom_retry_after(): void
    {
        $e = new RateLimitException('Slow down', 120);

        $this->assertSame(120, $e->getRetryAfter());
    }

    public function test_validation_exception_defaults(): void
    {
        $e = new ValidationException();

        $this->assertSame('Validation failed', $e->getMessage());
        $this->assertSame(422, $e->getCode());
        $this->assertSame([], $e->getErrors());
        $this->assertInstanceOf(ApiException::class, $e);
    }

    public function test_validation_exception_with_errors(): void
    {
        $errors = [
            'ico' => ['The ico must be 8 characters.'],
            'name' => ['The name field is required.'],
        ];

        $e = new ValidationException('Invalid input', $errors);

        $this->assertSame($errors, $e->getErrors());
        $this->assertSame(['The ico must be 8 characters.'], $e->getFieldErrors('ico'));
        $this->assertSame(['The name field is required.'], $e->getFieldErrors('name'));
        $this->assertSame([], $e->getFieldErrors('nonexistent'));
    }

    public function test_all_exceptions_extend_api_exception(): void
    {
        $this->assertInstanceOf(ApiException::class, new AuthenticationException());
        $this->assertInstanceOf(ApiException::class, new RateLimitException());
        $this->assertInstanceOf(ApiException::class, new ValidationException());
    }
}
