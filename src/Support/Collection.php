<?php

declare(strict_types=1);

namespace FirmApi\Support;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * A tiny, dependency-free collection wrapper. Deliberately NOT
 * illuminate/collections: this SDK stays framework-agnostic (only Guzzle),
 * so this provides the common ergonomics (map/filter/first/pluck/toArray)
 * without pulling in Laravel.
 *
 * @template TValue
 * @implements ArrayAccess<array-key, TValue>
 * @implements IteratorAggregate<array-key, TValue>
 */
final class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @param array<array-key, TValue> $items */
    public function __construct(private array $items = [])
    {
    }

    /**
     * @param iterable<array-key, TValue> $items
     * @return self<TValue>
     */
    public static function make(iterable $items = []): self
    {
        return new self(is_array($items) ? $items : iterator_to_array($items));
    }

    /** @return array<array-key, TValue> */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @template TMap
     * @param callable(TValue, array-key): TMap $callback
     * @return self<TMap>
     */
    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $mapped = array_map($callback, $this->items, $keys);

        return new self(array_combine($keys, $mapped));
    }

    /**
     * @param (callable(TValue, array-key): bool)|null $callback
     * @return self<TValue>
     */
    public function filter(?callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_values(array_filter($this->items)));
        }

        return new self(array_values(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH)));
    }

    /**
     * @param (callable(TValue, array-key): bool)|null $callback
     * @return TValue|null
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        foreach ($this->items as $key => $item) {
            if ($callback === null || $callback($item, $key)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * @param callable(TValue, array-key): void $callback
     * @return self<TValue>
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /** @return self<mixed> */
    public function pluck(string $key): self
    {
        return $this->map(static function ($item) use ($key) {
            if ($item instanceof ArrayAccess || is_array($item)) {
                return $item[$key] ?? null;
            }
            if (is_object($item)) {
                return $item->$key ?? null;
            }
            return null;
        });
    }

    /** @return self<TValue> */
    public function values(): self
    {
        return new self(array_values($this->items));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return array<array-key, mixed> */
    public function toArray(): array
    {
        return array_map(static function ($item) {
            if ($item instanceof self) {
                return $item->toArray();
            }
            if (is_object($item) && method_exists($item, 'toArray')) {
                return $item->toArray();
            }
            return $item;
        }, $this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /** @return Traversable<array-key, TValue> */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /** @return array<array-key, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
