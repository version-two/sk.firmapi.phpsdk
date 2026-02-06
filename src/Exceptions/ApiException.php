<?php

declare(strict_types=1);

namespace FirmApi\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        string $message = 'An API error occurred',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
