<?php

declare(strict_types=1);

namespace FirmApi\Objects;

/**
 * A single ORSR business activity ({activity, since?}). `since` may be absent
 * (the API omits null values).
 */
final class BusinessActivity implements \JsonSerializable
{
    public function __construct(
        public readonly string $activity,
        public readonly ?string $since = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            activity: (string) ($data['activity'] ?? ''),
            since: $data['since'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'activity' => $this->activity,
            'since' => $this->since,
        ], static fn ($v) => $v !== null);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
