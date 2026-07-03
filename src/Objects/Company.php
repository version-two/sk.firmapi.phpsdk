<?php

declare(strict_types=1);

namespace FirmApi\Objects;

use ArrayAccess;
use FirmApi\Support\Collection;
use JsonSerializable;

/**
 * A company lookup result.
 *
 * Wraps the raw API response ({data, meta}) in a typed, read-only value object
 * while remaining backward-compatible: array access still works, so
 * $company['data']['ico'] behaves exactly as the raw response did.
 *
 * Core registry fields are typed accessors; enrichment scopes (tax, sanctions,
 * financials, ...) — whose shapes are plan- and scope-dependent — are reached
 * generically via enrichment()/has() so the SDK never misrepresents their
 * structure.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Company implements ArrayAccess, JsonSerializable
{
    /**
     * @param array<string, mixed>          $raw           Full response ({data, meta}).
     * @param array<string, mixed>          $data          The `data` payload.
     * @param Collection<BusinessActivity>  $businessActivities
     * @param Collection<Shareholder>       $shareholders
     * @param Collection<StatutoryBody>     $statutoryBody
     */
    private function __construct(
        private readonly array $raw,
        private readonly array $data,
        public readonly ?string $ico,
        public readonly ?string $name,
        public readonly ?string $legalForm,
        public readonly ?string $legalFormCode,
        public readonly Address $address,
        public readonly ?string $establishedDate,
        public readonly ?string $terminatedDate,
        public readonly ?string $sourceRegister,
        public readonly ?string $status,
        public readonly ?string $orsrId,
        public readonly ?string $registeredCapital,
        public readonly Collection $businessActivities,
        public readonly Collection $shareholders,
        public readonly Collection $statutoryBody,
        public readonly Meta $meta,
    ) {
    }

    /** @param array<string, mixed> $response The decoded API response. */
    public static function fromResponse(array $response): self
    {
        /** @var array<string, mixed> $data */
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        /** @var array<string, mixed> $meta */
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];

        $address = is_array($data['address'] ?? null)
            ? Address::fromArray($data['address'])
            : new Address();

        return new self(
            raw: $response,
            data: $data,
            ico: $data['ico'] ?? null,
            name: $data['name'] ?? null,
            legalForm: $data['legal_form'] ?? null,
            legalFormCode: $data['legal_form_code'] ?? null,
            address: $address,
            establishedDate: $data['established_date'] ?? null,
            terminatedDate: $data['terminated_date'] ?? null,
            sourceRegister: $data['source_register'] ?? null,
            status: $data['status'] ?? null,
            orsrId: $data['orsr_id'] ?? null,
            registeredCapital: $data['registered_capital'] ?? null,
            businessActivities: self::mapList($data['business_activities'] ?? [], BusinessActivity::fromArray(...)),
            shareholders: self::mapList($data['shareholders'] ?? [], Shareholder::fromArray(...)),
            statutoryBody: self::mapList($data['statutory_body'] ?? [], StatutoryBody::fromArray(...)),
            meta: Meta::fromArray($meta),
        );
    }

    /**
     * Access an enrichment scope block (e.g. 'tax', 'sanctions', 'financials')
     * by its response key. Returns the raw value (array/scalar) or $default when
     * the scope was not requested / not entitled.
     */
    public function enrichment(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /** Whether an enrichment scope block is present in the response. */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /** The `data` payload as a raw array. */
    public function data(): array
    {
        return $this->data;
    }

    /** The full raw response ({data, meta}). */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @template T
     * @param mixed $rows
     * @param callable(array<string, mixed>): T $factory
     * @return Collection<T>
     */
    private static function mapList(mixed $rows, callable $factory): Collection
    {
        if (!is_array($rows)) {
            return new Collection();
        }

        $objects = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $objects[] = $factory($row);
            }
        }

        return new Collection($objects);
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
        throw new \LogicException('Company is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Company is read-only.');
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
