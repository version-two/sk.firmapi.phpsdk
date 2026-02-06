<?php

declare(strict_types=1);

namespace FirmApi\Exceptions;

class RateLimitException extends ApiException
{
    private int $retryAfter;

    public function __construct(
        string $message = 'Rate limit exceeded',
        int $retryAfter = 60,
        int $code = 429,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the number of seconds to wait before retrying.
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
