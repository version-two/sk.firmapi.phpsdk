<?php

declare(strict_types=1);

namespace FirmApi\Objects;

/**
 * One share issue of a joint-stock company. Mirrors the API's `shares[]`
 * entries ({share_type, share_form, share_state, nominal_value, currency,
 * count, transferability, effective_from, effective_to, current}).
 */
final class ShareIssue implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $shareType = null,
        public readonly ?string $shareForm = null,
        public readonly ?string $shareState = null,
        public readonly ?string $nominalValue = null,
        public readonly ?string $currency = null,
        public readonly ?int $count = null,
        public readonly ?string $transferability = null,
        public readonly ?string $effectiveFrom = null,
        public readonly ?string $effectiveTo = null,
        public readonly ?bool $current = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            shareType: $data['share_type'] ?? null,
            shareForm: $data['share_form'] ?? null,
            shareState: $data['share_state'] ?? null,
            nominalValue: $data['nominal_value'] ?? null,
            currency: $data['currency'] ?? null,
            count: isset($data['count']) ? (int) $data['count'] : null,
            transferability: $data['transferability'] ?? null,
            effectiveFrom: $data['effective_from'] ?? null,
            effectiveTo: $data['effective_to'] ?? null,
            current: isset($data['current']) ? (bool) $data['current'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'share_type' => $this->shareType,
            'share_form' => $this->shareForm,
            'share_state' => $this->shareState,
            'nominal_value' => $this->nominalValue,
            'currency' => $this->currency,
            'count' => $this->count,
            'transferability' => $this->transferability,
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
