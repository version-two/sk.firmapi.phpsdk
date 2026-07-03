<?php

declare(strict_types=1);

namespace FirmApi\Objects;

/**
 * A statutory-body member. Mirrors the API's `statutory_body[]` entries
 * ({name, role, address, acting_method}).
 */
final class StatutoryBody implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $role = null,
        public readonly ?string $address = null,
        public readonly ?string $actingMethod = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            role: $data['role'] ?? null,
            address: $data['address'] ?? null,
            actingMethod: $data['acting_method'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
            'address' => $this->address,
            'acting_method' => $this->actingMethod,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
