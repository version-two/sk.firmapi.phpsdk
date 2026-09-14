<?php

declare(strict_types=1);

namespace FirmApi\Objects;

/**
 * A statutory-body member. Mirrors the API's `statutory_body[]` entries
 * ({name, role, function, body_type, address, acting_method, appointed_at,
 * effective_from, effective_to, current}).
 */
final class StatutoryBody implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $role = null,
        public readonly ?string $function = null,
        public readonly ?string $bodyType = null,
        public readonly ?string $address = null,
        public readonly ?string $actingMethod = null,
        public readonly ?string $appointedAt = null,
        public readonly ?string $effectiveFrom = null,
        public readonly ?string $effectiveTo = null,
        public readonly ?bool $current = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            role: $data['role'] ?? null,
            function: $data['function'] ?? null,
            bodyType: $data['body_type'] ?? null,
            address: $data['address'] ?? null,
            actingMethod: $data['acting_method'] ?? null,
            appointedAt: $data['appointed_at'] ?? null,
            effectiveFrom: $data['effective_from'] ?? null,
            effectiveTo: $data['effective_to'] ?? null,
            current: isset($data['current']) ? (bool) $data['current'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
            'function' => $this->function,
            'body_type' => $this->bodyType,
            'address' => $this->address,
            'acting_method' => $this->actingMethod,
            'appointed_at' => $this->appointedAt,
            'effective_from' => $this->effectiveFrom,
            'effective_to' => $this->effectiveTo,
            'current' => $this->current,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
