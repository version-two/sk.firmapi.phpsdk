<?php

declare(strict_types=1);

namespace FirmApi\Objects;

use ArrayAccess;
use JsonSerializable;

/**
 * Response metadata. The exact keys vary by endpoint/state, so the well-known
 * freshness fields are exposed as typed accessors while the full map remains
 * available via raw()/array access.
 *
 * `stale = true` means the returned data is valid but a background refresh is
 * queued; `retryAt` hints when the refreshed version should be ready.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Meta implements ArrayAccess, JsonSerializable
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        private readonly array $raw,
        public readonly bool $stale = false,
        public readonly ?string $retryAt = null,
        public readonly ?string $staleReason = null,
        public readonly ?string $source = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            raw: $data,
            stale: (bool) ($data['stale'] ?? false),
            retryAt: $data['retry_at'] ?? null,
            staleReason: $data['stale_reason'] ?? null,
            source: $data['source'] ?? null,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->raw;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->raw);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->raw[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Meta is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Meta is read-only.');
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
