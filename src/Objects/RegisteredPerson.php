<?php

declare(strict_types=1);

namespace FirmApi\Objects;

/**
 * A person or company registered in one of the company's bodies. Mirrors
 * the entries of `supervisory_board[]`, `procurators[]`, `liquidators[]`,
 * `administrators[]`, `founders[]`, `branch_heads[]`, `legal_predecessors[]`
 * and `other_stakeholders[]` ({name, address, is_company, ico,
 * stakeholder_type, function, acting_method, effective_from, effective_to,
 * current}).
 */
final class RegisteredPerson implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?bool $isCompany = null,
        public readonly ?string $ico = null,
        public readonly ?string $stakeholderType = null,
        public readonly ?string $function = null,
        public readonly ?string $actingMethod = null,
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
            address: $data['address'] ?? null,
            isCompany: isset($data['is_company']) ? (bool) $data['is_company'] : null,
            ico: $data['ico'] ?? null,
            stakeholderType: $data['stakeholder_type'] ?? null,
            function: $data['function'] ?? null,
            actingMethod: $data['acting_method'] ?? null,
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
            'address' => $this->address,
            'is_company' => $this->isCompany,
            'ico' => $this->ico,
            'stakeholder_type' => $this->stakeholderType,
            'function' => $this->function,
            'acting_method' => $this->actingMethod,
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
