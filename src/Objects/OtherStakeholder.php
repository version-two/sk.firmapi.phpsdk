<?php

declare(strict_types=1);

namespace FirmApi\Objects;

/**
 * A registered person without an equity stake (supervisory board member,
 * procurator, liquidator, administrator...). Mirrors the API's
 * `other_stakeholders[]` entries ({name, address, is_company, ico,
 * stakeholder_type, effective_from, effective_to, current}).
 */
final class OtherStakeholder implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?bool $isCompany = null,
        public readonly ?string $ico = null,
        public readonly ?string $stakeholderType = null,
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
