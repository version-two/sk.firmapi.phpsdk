<?php

declare(strict_types=1);

namespace FirmApi\Exceptions;

class ValidationException extends ApiException
{
    /** @var array<string, array<string>> */
    private array $errors;

    /**
     * @param string $message
     * @param array<string, array<string>> $errors
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field.
     *
     * @param string $field
     * @return array<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
}
