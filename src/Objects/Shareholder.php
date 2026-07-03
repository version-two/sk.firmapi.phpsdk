<?php

declare(strict_types=1);

namespace FirmApi\Objects;

/**
 * A company shareholder. Mirrors the API's `shareholders[]` entries
 * ({name, address, share_amount, share_percentage, is_company, ico}).
 */
final class Shareholder implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?string $shareAmount = null,
        public readonly ?string $sharePercentage = null,
        public readonly ?bool $isCompany = null,
        public readonly ?string $ico = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            address: $data['address'] ?? null,
            shareAmount: $data['share_amount'] ?? null,
            sharePercentage: $data['share_percentage'] ?? null,
            isCompany: isset($data['is_company']) ? (bool) $data['is_company'] : null,
            ico: $data['ico'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'share_amount' => $this->shareAmount,
            'share_percentage' => $this->sharePercentage,
            'is_company' => $this->isCompany,
            'ico' => $this->ico,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
