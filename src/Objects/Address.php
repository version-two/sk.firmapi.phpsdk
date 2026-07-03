<?php

declare(strict_types=1);

namespace FirmApi\Objects;

/**
 * Company seat address. Mirrors the API's nested `address` object
 * ({street, city, postal_code, country}).
 */
final class Address implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $country = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            street: $data['street'] ?? null,
            city: $data['city'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            country: $data['country'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }

    /** A single-line "Street, PSČ City, Country" rendering (nulls skipped). */
    public function formatted(): string
    {
        $cityLine = trim(($this->postalCode ?? '') . ' ' . ($this->city ?? ''));
        $parts = array_filter([$this->street, $cityLine !== '' ? $cityLine : null, $this->country]);

        return implode(', ', $parts);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
